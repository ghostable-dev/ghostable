<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project\DeploymentToken;

use App\Account\Models\User;
use App\Api\V2\Http\Controllers\Concerns\LogsDeploymentTokenActivity;
use App\Api\V2\Project\Presenters\DeploymentTokenPresenter;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\Token\RevokeDeploymentToken as RevokeDeploymentTokenAction;
use App\Environment\Models\DeploymentToken;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RevokeDeploymentToken extends Controller
{
    use LogsDeploymentTokenActivity;

    public function __invoke(
        Request $request,
        Project $project,
        DeploymentToken $deploymentToken,
        RevokeDeploymentTokenAction $revokeDeploymentToken,
        DeploymentTokenPresenter $presenter
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $this->ensureProjectAccess($project, $user);
        $this->ensureTokenBelongsToProject($deploymentToken, $project);

        $token = $revokeDeploymentToken->handle($deploymentToken, $user);

        $resource = $presenter->present($token);
        $resource['meta'] = [
            'success' => true,
        ];

        $token->loadMissing('environment.project');
        $environment = $token->environment;

        if (! $environment) {
            abort(422, 'Deployment token is not associated with an environment.');
        }

        $this->logDeploymentTokenActivity(
            event: 'deployment_token_revoked',
            message: "Revoked deployment token \"{$token->name}\" for \"{$environment->name}\" via cli.",
            deploymentToken: $token,
            project: $project,
            environment: $environment,
            user: $user,
            request: $request,
            context: [
                'result' => [
                    'status' => 'revoked',
                ],
            ],
        );

        return response()->json($resource);
    }

    private function ensureProjectAccess(Project $project, User $user): void
    {
        if (! $user->isOrganizationAdmin($project->organization)) {
            abort(403);
        }
    }

    private function ensureTokenBelongsToProject(DeploymentToken $token, Project $project): void
    {
        if ($token->project_id !== $project->getKey()) {
            abort(404);
        }
    }
}
