<?php

namespace App\Environment\Notifications;

class EnvironmentCreatedNotification extends EnvironmentNotification
{
    protected function mailView(): string
    {
        return 'mail.environment.created';
    }

    protected function subject(): string
    {
        return 'New environment created';
    }

    protected function messageLine(): string
    {
        return "New environment named \"{$this->environment->name}\" created in the \"{$this->environment->project->name}\" project of the \"{$this->forOrganization()->name}\" organization.";
    }
}
