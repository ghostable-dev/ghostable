<?php

namespace App\Project\Actions;

use App\Project\Entities\UpdateProjectSettingsPayload;
use App\Project\Events\ProjectSettingsChanged;
use App\Project\Models\Project;

class UpdateProjectSettings
{
    public function handle(Project $project, UpdateProjectSettingsPayload $payload): Project
    {
        $project->update([
            'name' => $payload->name,
            'description' => $payload->description,
            'deployment_provider' => $payload->deploymentProvider,
        ]);

        ProjectSettingsChanged::dispatch($project);

        return $project;
    }
}
