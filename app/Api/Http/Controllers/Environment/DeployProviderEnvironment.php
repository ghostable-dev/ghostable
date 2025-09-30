<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Environment;

use App\Api\Resources\Environment\DeploymentResource;
use App\Environment\Deployment\DeploymentProviderResolver;
use App\Environment\Models\Environment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

class DeployProviderEnvironment extends DeployEnvironment
{
    /**
     * Validate and return environment variables for provider-specific deployment.
     */
    public function __invoke(): JsonResponse|JsonResource
    {
        $environment = $this->resolveEnvironmentFromToken();

        try {
            $this->validate($environment);
        } catch (ValidationException $e) {
            return $this->validationErrors($e);
        }

        try {
            return $this->buildDeploymentResource(
                environment: $environment,
                encrypted: request()->boolean('encrypted', false)
            );
        } catch (InvalidArgumentException $e) {
            return $this->unsupportedDeploymentProvider();
        } catch (Throwable $e) {
            return $this->deploymentDataPreparationFailed($environment, $e);
        }
    }

    protected function buildDeploymentResource(Environment $environment, bool $encrypted = false): DeploymentResource
    {
        $handler = app(DeploymentProviderResolver::class)
            ->resolve($environment->project->deployment_provider->value);

        return new DeploymentResource(
            $handler->toData(environment: $environment, encrypted: $encrypted)
        );
    }

    protected function unsupportedDeploymentProvider(): JsonResponse
    {
        return response()->json([
            'message' => 'Unsupported deployment provider.',
        ], 400);
    }

    protected function deploymentDataPreparationFailed(Environment $environment, Throwable $e): JsonResponse
    {
        Log::error('Deployment data preparation failed', [
            'environment_id' => $environment->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'message' => 'An error occurred preparing the deployment data.',
        ], 500);
    }
}
