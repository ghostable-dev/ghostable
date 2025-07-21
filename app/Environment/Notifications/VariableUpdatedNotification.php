<?php

namespace App\Environment\Notifications;

use App\Core\Notifications\BaseNotification;
use App\Environment\Models\EnvironmentVariable;
use Illuminate\Notifications\Messages\MailMessage;

class VariableUpdatedNotification extends BaseNotification
{
    public function __construct(protected EnvironmentVariable $variable) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Variable Updated')
            ->line("Variable '{$this->variable->key}' was updated in environment '{$this->variable->environment->name}'.");
    }
}
