<?php

namespace App\Auth\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class VerifyEmailNotification extends BaseVerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject(Lang::get('Verify your email'))
            ->view('mail.auth.verify-email', [
                'title' => Lang::get('Verify email'),
                'url' => $url,
                'notifiable' => $notifiable,
            ]);
    }
}
