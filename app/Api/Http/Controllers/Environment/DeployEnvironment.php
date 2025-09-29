<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Environment;

use App\Api\Resources\Environment\EnvironmentVariableResource;
use App\Api\Responses\Environment\VaporSpecsResponse;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\ValidateEnvironment as Validate;
use App\Project\Enums\DeploymentProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;
use Throwable;

final class DeployEnvironment extends Controller
{
    /**
     * Validate and return environment variables for deployment.
     */
    public function __invoke(): JsonResponse|JsonResource
    {
        $environment = $this->resolveEnvironmentFromToken();

        // Validate
        try {
            app(Validate::class)->handle($environment);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        $resolved = resolve(ResolveEnvironmentVariables::class)->handle($environment);

        if ($environment->project->deployment_provider === DeploymentProvider::LARAVEL_VAPOR) {
            $specs = VaporSpecsResponse::build(
                $resolved,
                $this->resolveVaporProviderParams($environment),
                $this->makeVaporEncryptor($environment),
            );

            return response()->json($specs->toArray());
        }

        return EnvironmentVariableResource::collection($resolved);
    }

    private function resolveEnvironmentFromToken(): Environment
    {
        $actor = request()->user();

        if (! $actor instanceof Environment || ! $actor->tokenCan('deploy')) {
            throw new AuthorizationException(
                'The provided token is invalid, lacks deploy scope, or does not correspond to an environment.'
            );
        }

        return $actor;
    }

    /**
     * Resolve provider-specific parameters for Laravel Vapor deployments.
     *
     * @return array<string, mixed>
     */
    private function resolveVaporProviderParams(Environment $environment): array
    {
        $project = $environment->project;

        $params = $project->deployment_provider_params ?? [];

        if (is_string($params)) {
            $decoded = json_decode($params, true);

            $params = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($params)) {
            $params = [];
        }

        $params['stage'] ??= $environment->name;

        return array_filter(
            $params,
            static fn ($value) => $value !== null && $value !== ''
        );
    }

    /**
     * Build an encryptor callback for bundling encrypted deployment values.
     */
    private function makeVaporEncryptor(Environment $environment): callable
    {
        return static function (array $variables) use ($environment): array {
            ksort($variables);

            $normalized = array_map(
                static fn ($value) => $value === null ? '' : (string) $value,
                $variables,
            );

            try {
                $payload = json_encode($normalized, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new RuntimeException('Unable to encode Vapor variable bundle.', 0, $exception);
            }

            try {
                $bundle = $environment->encrypter()->encryptString($payload);
            } catch (Throwable $exception) {
                throw new RuntimeException('Unable to encrypt Vapor variable bundle.', 0, $exception);
            }

            return [
                'bundle' => $bundle,
                'included_keys' => array_keys($normalized),
            ];
        };
    }
}
