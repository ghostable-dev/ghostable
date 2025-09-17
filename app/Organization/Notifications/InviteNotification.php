<?php

namespace App\Organization\Notifications;

use App\Organization\Models\Invite;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteNotification extends Notification
{
    protected bool $unsubscribable = false;

    public function __construct(protected Invite $invite) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf(
                'Ghostable invitation: Join %s',
                $this->invite->organization->name,
            ))
            ->view('mail.invite', [
                'organization' => $this->invite->organization,
            ]);
    }
}
