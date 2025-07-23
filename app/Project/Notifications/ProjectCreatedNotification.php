<?php

namespace App\Project\Notifications;

class ProjectCreatedNotification extends ProjectNotification
{    
    protected function subject(): string
    {
        return 'New project created';
    }

    protected function messageLine(): string
    {
        return "New project named \"{$this->project->name}\" created in the \"{$this->project->team->name}\" team.";
    }
}
