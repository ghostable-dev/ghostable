<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project\DeploymentToken;

use App\Account\Models\User;
use App\Api\V2\Http\Controllers\Concerns\LogsDeploymentTokenActivity;
use App\Api\V2\Project\Presenters\DeploymentTokenPresenter;
use App\Api\V2\Project\Requests\StoreDeploymentTokenRequest;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\Token\CreateDeploymentToken as CreateDeploymentTokenAction;
use App\Environment\Models\Environment;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;

final class CreateDeploymentToken extends Controller
{
    use LogsDeploymentTokenActivity;

    public function __invoke(
        StoreDeploymentTokenRequest $request,
        Project $project,
        CreateDeploymentTokenAction $createDeploymentToken,
        DeploymentTokenPresenter $presenter
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validated();

        /** @var Environment $environment */
        $environment = $project->environments()->whereKey($data['environment_id'])->firstOrFail();

        $this->authorize('manageTokens', $environment);

        $result = $createDeploymentToken->handle(
            name: $data['name'],
            environment: $environment,
            publicKey: $data['public_key'],
            user: $user,
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

        $this->logDeploymentTokenActivity(
            event: 'deployment_token_created',
            message: sprintf(
                'Created deployment token "%s" for "%s" via %s.',
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
                    'environment_id' => (string) $environment->getKey(),
                    'expires_after' => $data['expires_after'] ?? 90,
                ],
                'result' => [
                    'personal_access_token_id' => (string) $sanctumToken->getKey(),
                    'token_suffix' => $result->token->token_suffix,
                    'expires_at' => $sanctumToken->expires_at?->toIso8601String(),
                ],
            ],
        );

        return response()->json($resource, 201);
    }
}
