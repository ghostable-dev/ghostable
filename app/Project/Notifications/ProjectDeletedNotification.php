<?php

namespace App\Project\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ProjectDeletedNotification extends ProjectNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf(
                'Ghostable update: Project "%s" deleted',
                $this->project->name,
            ))
            ->view('mail.project-deleted', ['project' => $this->project]);
    }

    public function toSlack(object $notifiable): string
    {
        return sprintf(
            'The project "%s" was deleted from the "%s" organization on Ghostable.',
            $this->project->name,
            $this->project->organization->name,
        );
    }
}
