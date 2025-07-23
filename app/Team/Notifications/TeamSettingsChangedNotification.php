<?php

namespace App\Team\Notifications;

use App\Team\Models\Team;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamSettingsChangedNotification extends Notification
{
    protected bool $unsubscribable = true;

    public function __construct(protected Team $team) 
    {}

    public function via(object $notifiable): array
    {
        return ['mail', 'slack'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->line($this->messageLine())
            ->line('You are receiving this alert because you are an administrator of this team.');
    }

    public function toSlack(object $notifiable): string
    {
        return $this->messageLine();
    }

    protected function subject(): string
    {
        return "Team settings changed";
    }

    protected function messageLine(): string
    {
        return "Team settings for the \"{$this->team->name}\" team has been updated.";
    }
}