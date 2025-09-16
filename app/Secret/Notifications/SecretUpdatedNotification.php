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
            ->subject('Secret Updated')
            ->view('mail.secret-updated', [
                'title' => 'Secret updated',
                'secret' => $this->secret,
            ]);
    }

    public function toSlack(object $notifiable): string
    {
        return "Secret '{$this->secret->name}' was updated.";
    }
}
