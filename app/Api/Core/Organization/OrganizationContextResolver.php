<?php

namespace App\Api\Core\Organization;

use App\Environment\Models\Environment;
use App\Organization\Models\Organization;
use App\Organization\Resolvers\ResolveOrganization;
use App\Project\Models\Project;
use App\Project\Resolvers\ResolveProject;
use Illuminate\Http\Request;

class OrganizationContextResolver
{
    /**
     * Resolve the billing/limiting Organization for the current API request.
     */
    public function resolveFromRequest(Request $request): ?Organization
    {
        // Route-model bound parameters (fast path, zero extra queries if relations are eager-loaded) ---
        $route = $request->route();
        if ($route) {
            foreach ($route->parameters() as $param) {
                if ($org = $this->inferOrgFromModel($param)) {
                    return $org;
                }
            }
        }

        // Environment-token based auth (the "user" is an Environment model) ---
        // Example: Route::get('/ci/deploy', DeployEnvironment::class);
        // If your Sanctum/guard returns Environment as the authenticated principal,
        // we can derive the org from it.
        $auth = $request->user();
        if ($auth instanceof Environment) {
            return $this->inferOrgFromModel($auth);
        }

        // Not resolvable (non-org-scoped or intentionally unmetered endpoint)
        return null;
    }

    /**
     * Given any known domain model, try to derive its owning Organization.
     */
    private function inferOrgFromModel(mixed $model): ?Organization
    {
        // Direct org
        if ($model instanceof Organization) {
            return $model;
        }

        // Project → Organization
        if ($model instanceof Project) {
            return ResolveOrganization::onceWithContext($model->organization_id);
        }

        // Environment → Project → Organization
        if ($model instanceof Environment) {
            $project = ResolveProject::onceWithContext($model->project_id);

            return $this->inferOrgFromModel($project);
        }

        return null;
    }
}
