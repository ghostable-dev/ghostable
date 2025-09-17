<?php

namespace App\Auth\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends BaseResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $expiration = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject(Lang::get('Ghostable account: Reset your password'))
            ->view('mail.auth.reset-password', [
                'title' => Lang::get('Reset password'),
                'url' => $url,
                'expiration' => $expiration,
                'notifiable' => $notifiable,
            ]);
    }
}
