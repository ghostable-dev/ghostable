<?php

namespace App\Organization\Http\Middleware;

use App\Environment\Models\Environment;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLegacyOrganizationExperience
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $organization = $this->resolveOrganization($request);

        if ($organization?->usesDesktopLicensing()) {
            abort(Response::HTTP_FORBIDDEN, 'This organization uses the desktop licensing experience.');
        }

        return $next($request);
    }

    private function resolveOrganization(Request $request): ?Organization
    {
        $actor = $request->user();

        if ($actor instanceof Environment) {
            return $actor->owningOrganization();
        }

        $organization = $request->route('organization');

        if ($organization instanceof Organization) {
            return $organization;
        }

        if (is_string($organization)) {
            /** @var Organization|null $resolvedOrganization */
            $resolvedOrganization = Organization::query()->find($organization);

            return $resolvedOrganization;
        }

        $submittedOrganizationId = $request->input('organization_id');

        if (is_string($submittedOrganizationId)) {
            $submittedOrganization = Organization::query()->find($submittedOrganizationId);

            if ($submittedOrganization instanceof Organization) {
                return $submittedOrganization;
            }
        }

        $project = $request->route('project');

        if ($project instanceof Project) {
            return $project->owningOrganization();
        }

        if (is_string($project)) {
            $resolvedProject = Project::query()->find($project);

            if ($resolvedProject instanceof Project) {
                return $resolvedProject->owningOrganization();
            }
        }

        $environment = $request->route('environment');

        if ($environment instanceof Environment) {
            return $environment->owningOrganization();
        }

        if (is_string($environment)) {
            $resolvedEnvironment = Environment::query()->find($environment);

            if ($resolvedEnvironment instanceof Environment) {
                return $resolvedEnvironment->owningOrganization();
            }
        }

        if (! $request->is('api/*')) {
            return $request->user()?->currentOrganization();
        }

        return null;
    }
}
