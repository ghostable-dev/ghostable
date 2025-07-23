<?php

namespace App\Project\Notifications;

use App\Environment\Notifications\EnvironmentNotification;

class EnvironmentDeletedNotification extends EnvironmentNotification
{
    protected function subject(): string
    {
        return 'New environment deleted';
    }

    protected function messageLine(): string
    {
        return "Environment named \"{$this->environment->name}\" was deleted in the \"{$this->environment->project->name}\" project of the \"{$this->environment->team->name}\" team.";
    }
}
