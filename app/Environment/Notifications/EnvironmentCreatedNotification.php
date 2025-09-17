<?php

namespace App\Environment\Notifications;

class EnvironmentCreatedNotification extends EnvironmentNotification
{
    protected function mailView(): string
    {
        return 'mail.environment.created';
    }

    protected function title(): string
    {
        return 'Environment created';
    }

    protected function subject(): string
    {
        return sprintf(
            'Ghostable update: Environment "%s" created',
            $this->environment->name,
        );
    }

    protected function messageLine(): string
    {
        return sprintf(
            'A new environment named "%s" was created for the "%s" project in the "%s" organization on Ghostable.',
            $this->environment->name,
            $this->environment->project->name,
            $this->forOrganization()->name,
        );
    }
}
