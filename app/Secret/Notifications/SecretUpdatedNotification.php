<?php

namespace App\Secret\Notifications;

use App\Core\Notifications\BaseNotification;
use App\Secret\Models\Secret;
use Illuminate\Notifications\Messages\MailMessage;

class SecretUpdatedNotification extends BaseNotification
{
    public function __construct(protected Secret $secret) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf(
                'Ghostable update: Secret "%s" updated',
                $this->secret->name,
            ))
            ->view('mail.secret-updated', [
                'title' => 'Secret updated',
                'secret' => $this->secret,
            ]);
    }

    public function toSlack(object $notifiable): string
    {
        return sprintf(
            'The "%s" secret was updated on Ghostable.',
            $this->secret->name,
        );
    }
}
