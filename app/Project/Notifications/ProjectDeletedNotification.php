<?php

namespace App\Project\Notifications;

use App\Project\Notifications\ProjectNotification;

class ProjectDeletedNotification extends ProjectNotification
{
    protected function subject(): string
    {
        return "Project \"{$this->project->name}\" deleted";
    }
    
    protected function messageLine(): string
    {
        return "The project named \"{$this->project->name}\" has been deleted from the \"{$this->project->team->name}\" team.";
    }
}