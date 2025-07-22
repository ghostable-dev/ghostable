<?php

namespace App\Project\Notifications;

use App\Core\Notifications\BaseNotification;
use App\Environment\Models\Environment;
use Illuminate\Notifications\Messages\MailMessage;

class EnvironmentCreatedNotification extends BaseNotification
{
    public function __construct(protected Environment $environment) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Environment Created')
            ->line("Environment '{$this->environment->name}' was created in project '{$this->environment->project->name}'.");
    }

    public function toSlack(object $notifiable): string
    {
        return "Environment '{$this->environment->name}' was created in project '{$this->environment->project->name}'.";
    }
}
