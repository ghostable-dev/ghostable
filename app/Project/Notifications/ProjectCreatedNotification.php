<?php

namespace App\Project\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ProjectCreatedNotification extends ProjectNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Ghostable project created')
            ->view('mail.project-created', ['project' => $this->project]);
    }
    
    public function toSlack(object $notifiable): string
    {
        return sprintf(
            "New project named %s created in the %s organization of Ghostable.", 
            $this->project->name, 
            $this->project->organization->name
        );
    }
}
