<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Environment\V2;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\BuildEncryptedProjection;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PullEnvironment extends Controller
{
    /**
     * GET /projects/{project}/environments/{name}/pull
     *
     * Returns an encrypted projection bundle (no plaintext).
     * Authorization: Requires 'ViewVariables' on the environment.
     *
     * Optional query params:
     *   - only[]=KEY      // restrict to specific variable names (repeatable)
     *   - include_meta=1  // include line_bytes/is_* flags in each entry
     *   - include_versions=1 // include each secret's head version in entries
     */
    public function __invoke(Request $request, Project $project, string $name): JsonResponse
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, OrganizationPermission::ViewVariables]);

        $only             = (array) $request->query('only', []);
        $includeMeta      = (bool) filter_var($request->query('include_meta', false), FILTER_VALIDATE_BOOLEAN);
        $includeVersions  = (bool) filter_var($request->query('include_versions', false), FILTER_VALIDATE_BOOLEAN);

        $bundle = app(BuildEncryptedProjection::class)->handle(
            environment: $env,
            only: $only,
            includeMeta: $includeMeta,
            includeVersions: $includeVersions
        );

        // Optional: strong caching & ETag based on chain+HMACs
        return response()->json($bundle, 200);
    }
}