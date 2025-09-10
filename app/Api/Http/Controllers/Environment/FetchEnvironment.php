<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Environment;

use App\Api\Resources\Environment\EnvironmentVariableResource;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FetchEnvironment extends Controller
{
    /**
     * Fetch current environment variables.
     *
     * Authorization: Requires 'ViewVariables' permission on the environment.
     */
    public function __invoke(Request $request, Project $project, string $name): JsonResource
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, OrganizationPermission::ViewVariables]);

        $vars = resolve(ResolveEnvironmentVariables::class)->handle($env);

        return EnvironmentVariableResource::collection($vars);
    }
}
