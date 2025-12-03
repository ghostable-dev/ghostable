<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\BuildEncryptedProjection;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Environment\Support\DeploymentTokenAuditProperties;
use App\Environment\Support\EnvironmentAuditProperties;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeployEnvironment extends Controller
{
    /**
     * Validate and return environment variables for deployment.
     */
    public function __invoke(Request $request): JsonResponse|JsonResource
    {
        $environment = $this->resolveEnvironmentFromToken();

        $accessToken = $environment->currentAccessToken();

        $deploymentToken = null;

        if ($accessToken) {
            $deploymentToken = DeploymentToken::query()
                ->where('personal_access_token_id', $accessToken->getKey())
                ->first();
        }

        $only = (array) $request->query('only', []);
        $includeMeta = (bool) filter_var($request->query('include_meta', true), FILTER_VALIDATE_BOOLEAN);
        $includeVersions = (bool) filter_var($request->query('include_versions', true), FILTER_VALIDATE_BOOLEAN);

        $bundle = app(BuildEncryptedProjection::class)->handle(
            environment: $environment,
            only: $only,
            includeMeta: $includeMeta,
            includeVersions: $includeVersions,
            includeLegacyEnvironmentKey: false,
            deploymentToken: $deploymentToken,
        );

        $onlyNames = collect($only)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();

        $secretsReturned = count($bundle['secrets'] ?? []);

        $this->logDeploymentRequest(
            request: $request,
            environment: $environment,
            deploymentToken: $deploymentToken,
            onlyNames: $onlyNames,
            includeMeta: $includeMeta,
            includeVersions: $includeVersions,
            secretsReturned: $secretsReturned,
        );

        return response()->json($bundle, 200);
    }

    protected function resolveEnvironmentFromToken(): Environment
    {
        $actor = request()->user();

        if (! $actor instanceof Environment || ! $actor->tokenCan('deploy')) {
            throw new AuthorizationException(
                'The provided token is invalid, lacks deploy scope, or does not correspond to an environment.'
            );
        }

        return $actor;
    }

    private function logDeploymentRequest(
        Request $request,
        Environment $environment,
        ?DeploymentToken $deploymentToken,
        array $onlyNames,
        bool $includeMeta,
        bool $includeVersions,
        int $secretsReturned
    ): void {
        $properties = [
            'source' => 'deploy-api',
            'environment' => EnvironmentAuditProperties::make($environment),
            'project' => [
                'id' => (string) $environment->project_id,
                'name' => $environment->project?->name,
            ],
            'deployment_token' => $deploymentToken
                ? DeploymentTokenAuditProperties::make($deploymentToken)
                : null,
            'request' => [
                'filters' => [
                    'only' => $onlyNames,
                    'only_count' => count($onlyNames),
                    'include_meta' => $includeMeta,
                    'include_versions' => $includeVersions,
                ],
            ],
            'result' => [
                'secrets_returned' => $secretsReturned,
            ],
            'ip_address' => $request->ip(),
        ];

        $logger = activity('variable')
            ->performedOn($environment)
            ->event('deploy');

        if ($deploymentToken) {
            $logger->causedBy($deploymentToken);
        }

        $tokenName = $deploymentToken?->name ?? 'deployment token';

        $logger->withProperties(array_filter($properties, static fn ($value) => $value !== null))
            ->log("Deployment token \"{$tokenName}\" pulled \"{$environment->name}\" for deploy.");
    }
}
