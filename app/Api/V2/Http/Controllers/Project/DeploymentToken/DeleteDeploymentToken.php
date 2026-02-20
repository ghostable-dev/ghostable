<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project\DeploymentToken;

use App\Account\Models\User;
use App\Api\V2\Http\Controllers\Concerns\LogsDeploymentTokenActivity;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\Token\DeleteEnvToken;
use App\Environment\Models\DeploymentToken;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeleteDeploymentToken extends Controller
{
    use LogsDeploymentTokenActivity;

    public function __invoke(
        Request $request,
        Project $project,
        DeploymentToken $deploymentToken,
        DeleteEnvToken $deleteEnvToken
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $this->ensureProjectAccess($project, $user);
        $this->ensureTokenBelongsToProject($deploymentToken, $project);
        $this->ensureTokenIsRevoked($deploymentToken);

        $deploymentToken->loadMissing('environment.project', 'personalAccessToken');
        $environment = $deploymentToken->environment;

        if (! $environment) {
            abort(422, 'Deployment token is not associated with an environment.');
        }

        $tokenId = (string) $deploymentToken->getKey();
        $tokenName = $deploymentToken->name;
        $patId = $deploymentToken->personal_access_token_id;

        if ($deploymentToken->personalAccessToken) {
            $deleteEnvToken->handle($deploymentToken->personalAccessToken, $user);
            $deploymentToken->unsetRelation('personalAccessToken');
        }

        $deploymentToken->delete();

        $this->logDeploymentTokenActivity(
            event: 'deployment_token_deleted',
            message: "Deleted deployment token \"{$tokenName}\" for \"{$environment->name}\" via api.",
            deploymentToken: $deploymentToken,
            project: $project,
            environment: $environment,
            user: $user,
            request: $request,
            context: [
                'result' => [
                    'deleted_id' => $tokenId,
                    'personal_access_token_id' => $patId ? (string) $patId : null,
                ],
            ],
        );

        return response()->json([
            'meta' => [
                'success' => true,
                'deleted_id' => $tokenId,
            ],
        ]);
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

    private function ensureTokenIsRevoked(DeploymentToken $deploymentToken): void
    {
        if (! $deploymentToken->isRevoked()) {
            abort(422, 'Deployment token must be revoked before it can be deleted.');
        }
    }
}
