<?php

namespace App\Project\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ProjectSettingsChangedNotification extends ProjectNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf(
                'Ghostable update: Project "%s" settings updated',
                $this->project->name,
            ))
            ->view('mail.project-settings-changed', ['project' => $this->project]);
    }

    public function toSlack(object $notifiable): string
    {
        return sprintf(
            'Settings for the "%s" project in the "%s" organization were updated on Ghostable.',
            $this->project->name,
            $this->project->organization->name,
        );
    }
}
