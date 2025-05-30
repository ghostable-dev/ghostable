<?php

namespace App\Team\Notifications;

use App\Core\Notifications\BaseNotification;
use App\Team\Models\Team;
use Illuminate\Notifications\Messages\MailMessage;

class TeamInvitationNotification extends BaseNotification
{
    public function __construct(
        protected Team $team,
        protected string $email,
        protected string $inviteUrl
    ) {
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("You're invited to join {$this->team->name}")
            ->markdown('mail.team-invitation', [
                'team' => $this->team,
                'email' => $this->email,
                'inviteUrl' => $this->inviteUrl,
            ]);
    }
}
