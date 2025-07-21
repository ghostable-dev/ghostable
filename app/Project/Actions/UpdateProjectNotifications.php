<?php

namespace App\Project\Actions;

use App\Project\Models\Project;
use App\Project\Notifications\ProjectNotificationsData;

class UpdateProjectNotifications
{
    public function handle(Project $project, ProjectNotificationsData $data): Project
    {
        $project->update(['notifications' => $data]);

        return $project;
    }
}
