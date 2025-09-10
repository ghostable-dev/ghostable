<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Environment;

use App\Api\Resources\Environment\EnvironmentVariableResource;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\ValidateEnvironment as Validate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

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

        $vars = resolve(ResolveEnvironmentVariables::class)->handle($environment);

        return EnvironmentVariableResource::collection($vars);
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
}
