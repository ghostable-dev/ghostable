<?php

namespace App\Project\Notifications;

use App\Core\Notifications\BaseNotification;
use App\Environment\Models\Environment;
use Illuminate\Notifications\Messages\MailMessage;

class EnvironmentDeletedNotification extends BaseNotification
{
    public function __construct(protected Environment $environment) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Environment Deleted')
            ->line("Environment '{$this->environment->name}' was deleted from project '{$this->environment->project->name}'.");
    }
}
