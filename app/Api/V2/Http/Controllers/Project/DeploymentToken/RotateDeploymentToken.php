<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project\DeploymentToken;

use App\Account\Models\User;
use App\Api\V2\Http\Controllers\Concerns\LogsDeploymentTokenActivity;
use App\Api\V2\Project\Presenters\DeploymentTokenPresenter;
use App\Api\V2\Project\Requests\RotateDeploymentTokenRequest;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\Token\RotateDeploymentToken as RotateDeploymentTokenAction;
use App\Environment\Models\DeploymentToken;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class RotateDeploymentToken extends Controller
{
    use LogsDeploymentTokenActivity;

    public function __invoke(
        RotateDeploymentTokenRequest $request,
        Project $project,
        DeploymentToken $deploymentToken,
        RotateDeploymentTokenAction $rotateDeploymentToken,
        DeploymentTokenPresenter $presenter
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $this->ensureProjectAccess($project, $user);
        $this->ensureTokenBelongsToProject($deploymentToken, $project);
        $this->ensureTokenIsActive($deploymentToken);

        $data = $request->validated();

        $previousSuffix = $deploymentToken->token_suffix;

        $result = $rotateDeploymentToken->handle(
            deploymentToken: $deploymentToken,
            user: $user,
            publicKey: $data['public_key'] ?? null,
            expiresAfter: $data['expires_after'] ?? 90,
            recipient: $data['recipient'] ?? null,
        );

        $resource = $presenter->present($result->token);

        $sanctumToken = $result->accessToken->accessToken;

        $resource['meta'] = [
            'secret' => $result->plainTextSecret,
            'api_token' => [
                'plain_text' => $result->plainTextSecret,
                'id' => (string) $sanctumToken->getKey(),
                'name' => $sanctumToken->name,
                'token_suffix' => $result->token->token_suffix,
                'expires_at' => $sanctumToken->expires_at?->toIso8601String(),
            ],
        ];

        $result->token->loadMissing('environment.project');
        $environment = $result->token->environment;

        if (! $environment) {
            abort(422, 'Deployment token is not associated with an environment.');
        }

        $this->logDeploymentTokenActivity(
            event: 'deployment_token_rotated',
            message: sprintf(
                'Rotated deployment token "%s" for "%s" via %s.',
                $result->token->name,
                $environment->name,
                $this->resolveApiActivitySource($request)
            ),
            deploymentToken: $result->token,
            project: $project,
            environment: $environment,
            user: $user,
            request: $request,
            context: [
                'request' => [
                    'public_key_updated' => array_key_exists('public_key', $data) && $data['public_key'] !== null,
                    'expires_after' => $data['expires_after'] ?? null,
                ],
                'result' => [
                    'personal_access_token_id' => (string) $sanctumToken->getKey(),
                    'token_suffix_previous' => $previousSuffix,
                    'token_suffix_current' => $result->token->token_suffix,
                    'expires_at' => $sanctumToken->expires_at?->toIso8601String(),
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

    private function ensureTokenIsActive(DeploymentToken $deploymentToken): void
    {
        if ($deploymentToken->isRevoked()) {
            abort(422, 'Deployment token has been revoked and cannot be rotated.');
        }
    }
}
