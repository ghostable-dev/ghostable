<?php

namespace App\Project\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ProjectCreatedNotification extends ProjectNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf(
                'Ghostable update: Project "%s" created',
                $this->project->name,
            ))
            ->view('mail.project-created', ['project' => $this->project]);
    }

    public function toSlack(object $notifiable): string
    {
        return sprintf(
            'A new project named "%s" was created in the "%s" organization on Ghostable.',
            $this->project->name,
            $this->project->organization->name,
        );
    }
}
