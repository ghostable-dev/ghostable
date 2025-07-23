<?php

namespace App\Project\Actions;

use App\Project\Events\ProjectSettingsChanged;
use App\Project\Models\Project;

class UpdateProjectName
{
    public function handle(Project $project, string $name, string $description): Project
    {
        $project->update([
            'name' => $name,
            'description' => $description,
        ]);

        ProjectSettingsChanged::dispatch($project);

        return $project;
    }
}
