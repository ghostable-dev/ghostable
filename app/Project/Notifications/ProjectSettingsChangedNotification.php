<?php

namespace App\Project\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class ProjectSettingsChangedNotification extends ProjectNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ghostable project settings updated')
            ->view('mail.project-settings-changed', ['project' => $this->project]);
    }
    
    public function toSlack(object $notifiable): string
    {
        return sprintf(
            "Project settings for the project %s changed in the %s organization of Ghostable.", 
            $this->project->name, 
            $this->project->organization->name
        );
    }
}
