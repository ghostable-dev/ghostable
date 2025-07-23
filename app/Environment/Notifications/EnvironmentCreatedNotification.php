<?php

namespace App\Project\Notifications;

use App\Environment\Notifications\EnvironmentNotification;

class EnvironmentCreatedNotification extends EnvironmentNotification
{
    protected function subject(): string
    {
        return "New environment created";
    }
    
    protected function messageLine(): string
    {
        return "New environment named \"{$this->environment->name}\" created in the \"{$this->environment->project->name}\" project of the \"{$this->environment->team->name}\" team.";
    }
}