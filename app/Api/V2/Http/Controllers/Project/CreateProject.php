<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project;

use App\Api\Core\Resources\Project\ProjectResource;
use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use App\Project\Actions\CreateProject as CreateProjectAction;
use App\Project\Entities\CreateProjectPayload;
use App\Project\Entities\ProjectStackData;
use App\Project\Enums\DeploymentProvider;
use App\Project\Models\Project;
use App\Project\Rules\ProjectRules;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CreateProject extends Controller
{
    /**
     * Create a new project for the given organization.
     *
     * Authorization: Requires 'create' permission on the organization.
     */
    public function __invoke(Request $request, Organization $organization): JsonResource
    {
        $this->authorize('create', [Project::class, $organization]);

        if (! $request->has('deployment_provider')) {
            $request->merge(['deployment_provider' => DeploymentProvider::OTHER->value]);
        }

        $validated = $request->validate(ProjectRules::createRules($organization));

        $project = resolve(CreateProjectAction::class)->handle(
            new CreateProjectPayload(
                name: $validated['name'],
                organization: $organization,
                deploymentProvider: DeploymentProvider::from($validated['deployment_provider']),
                description: $validated['description'] ?? null,
                withDefaultEnvironments: $validated['with_default_environments'] ?? true,
                stack: isset($validated['stack'])
                    ? ProjectStackData::from($validated['stack'])
                    : null
            )
        );

        return new ProjectResource($project->load('environments'));
    }
}
