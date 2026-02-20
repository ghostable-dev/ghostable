<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project;

use App\Api\Core\Resources\Project\ProjectResource;
use App\Api\V2\Project\Requests\UpdateProjectSettingsRequest;
use App\Core\Http\Controllers\Controller;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Actions\UpdateProjectSettings;
use App\Project\Entities\ProjectStackData;
use App\Project\Entities\UpdateProjectSettingsPayload;
use App\Project\Enums\DeploymentProvider;
use App\Project\Models\Project;

final class UpdateProject extends Controller
{
    public function __invoke(
        UpdateProjectSettingsRequest $request,
        Project $project,
        UpdateProjectSettings $updateProjectSettings
    ): ProjectResource {
        $this->authorize('perform', [$project, OrganizationPermission::ManageProjectSettings]);

        $validated = $request->validated();

        $payload = new UpdateProjectSettingsPayload(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            deploymentProvider: DeploymentProvider::from($validated['deployment_provider']),
            stack: isset($validated['stack'])
                ? ProjectStackData::from($validated['stack'])
                : null,
        );

        $project = $updateProjectSettings->handle($project, $payload);

        return new ProjectResource($project->refresh());
    }
}
