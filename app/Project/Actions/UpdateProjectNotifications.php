<?php

namespace App\Project\Actions;

use App\Project\Entities\ProjectNotificationsData;
use App\Project\Models\Project;

class UpdateProjectNotifications
{
    public function handle(Project $project, ProjectNotificationsData $data): Project
    {
        $project->update(['notifications' => $data]);

        return $project;
    }
}
