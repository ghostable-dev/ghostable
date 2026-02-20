<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\BuildEncryptedProjection;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetEnvironmentKeys extends Controller
{
    public function __invoke(Request $request, Project $project, string $name): JsonResponse
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, OrganizationPermission::ViewVariables]);

        $only = (array) $request->query('only', []);
        $includeMeta = (bool) filter_var($request->query('include_meta', false), FILTER_VALIDATE_BOOLEAN);
        $includeVersions = (bool) filter_var($request->query('include_versions', false), FILTER_VALIDATE_BOOLEAN);

        // Build full projection so inheritance and visibility logic are preserved
        $bundle = app(BuildEncryptedProjection::class)->handle(
            environment: $env,
            only: $only,
            includeMeta: $includeMeta,
            includeVersions: $includeVersions,
        );

        // Return a minimal listing of key metadata — not values
        $data = collect($bundle['secrets'] ?? [])
            ->map(function (array $secret) {
                return [
                    'name' => $secret['name'],
                    'version' => $secret['version'] ?? null,     // only present if includeVersions=true
                    'is_commented' => (bool) ($secret['is_commented'] ?? data_get($secret, 'meta.is_commented', false)),
                    // You did select 'updated_at' in the query, but you never added it to $entry.
                    // Add it in BuildEncryptedProjection if you want it here.
                    'updated_at' => $secret['updated_at'] ?? null,
                    'updated_by_email' => $secret['updated_by_email'] ?? $secret['updated_by'] ?? null,
                ];
            })
            ->values();

        return response()->json([
            'project_id' => $project->id,
            'environment' => $env->name,
            'count' => $data->count(),
            'data' => $data,
        ], 200);
    }
}
