<?php

namespace App\Project\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ProjectDeletedNotification extends ProjectNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ghostable project deleted')
            ->view('mail.project-deleted', ['project' => $this->project]);
    }
    
    public function toSlack(object $notifiable): string
    {
        return sprintf(
            "The project named %s has been deleted from the %s organization of Ghostable.", 
            $this->project->name, 
            $this->project->organization->name
        );
    }
}
