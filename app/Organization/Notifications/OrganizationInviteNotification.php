<?php

namespace App\Organization\Notifications;

use App\Organization\Models\OrganizationInvite;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationInviteNotification extends Notification
{
    protected bool $unsubscribable = false;

    public function __construct(protected OrganizationInvite $invite) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're invited to join {$this->invite->organization->name}")
            ->line("{$this->invite->organization->owner->name} has invited you to join their organization in Ghostable.")
            ->action('Accept Invite', route('login'))
            ->line("This invitation was sent to {$this->invite->email} and will expire in 7 days.");
    }
}
