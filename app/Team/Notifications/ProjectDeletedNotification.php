<?php

namespace App\Team\Notifications;

use App\Core\Notifications\BaseNotification;
use App\Project\Models\Project;
use Illuminate\Notifications\Messages\MailMessage;

class ProjectDeletedNotification extends BaseNotification
{
    public function __construct(protected Project $project) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Project Deleted')
            ->line("Project '{$this->project->name}' was deleted.");
    }
}
