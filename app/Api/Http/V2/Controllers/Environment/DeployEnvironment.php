<?php

declare(strict_types=1);

namespace App\Api\Http\V2\Controllers\Environment;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\BuildEncryptedProjection;
use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\ValidateEnvironment as Validate;
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

        $only = (array) $request->query('only', []);
        $includeMeta = (bool) filter_var($request->query('include_meta', true), FILTER_VALIDATE_BOOLEAN);
        $includeVersions = (bool) filter_var($request->query('include_versions', true), FILTER_VALIDATE_BOOLEAN);

        $bundle = app(BuildEncryptedProjection::class)->handle(
            environment: $environment,
            only: $only,
            includeMeta: $includeMeta,
            includeVersions: $includeVersions
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
}
