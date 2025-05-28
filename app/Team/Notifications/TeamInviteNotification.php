<?php

namespace App\Team\Notifications;

use App\Team\Models\TeamInvite;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInviteNotification extends Notification
{
    protected bool $unsubscribable = false;

    public function __construct(protected TeamInvite $invite) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're invited to join {$this->invite->team->name}")
            ->line("{$this->invite->team->owner->name} has invited you to join their team in Ghostable.")
            ->action('Accept Invite', route('login'))
            ->line("This invitation was sent to {$this->invite->email} and will expire in 7 days.");
    }
}
