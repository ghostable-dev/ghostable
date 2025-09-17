<?php

namespace App\Environment\Notifications;

class EnvironmentDeletedNotification extends EnvironmentNotification
{
    protected function mailView(): string
    {
        return 'mail.environment.deleted';
    }

    protected function title(): string
    {
        return 'Environment deleted';
    }

    protected function subject(): string
    {
        return sprintf(
            'Ghostable update: Environment "%s" deleted',
            $this->environment->name,
        );
    }

    protected function messageLine(): string
    {
        return sprintf(
            'The "%s" environment was deleted from the "%s" project in the "%s" organization on Ghostable.',
            $this->environment->name,
            $this->environment->project->name,
            $this->forOrganization()->name,
        );
    }
}
