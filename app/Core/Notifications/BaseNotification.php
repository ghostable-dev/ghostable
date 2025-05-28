<?php

namespace App\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected bool $unsubscribable = false;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailMessage($notifiable);
    }

    protected function mailMessage(object $notifiable): MailMessage
    {
        $unsubscribeUrl = $this->unsubscribable && $this->hasUnsubscribeUrl($notifiable)
            ? $this->generateUnsubscribeUrl($notifiable)
            : null;

        return (new MailMessage)
            ->markdown('mail::message', [
                'notifiable' => $notifiable,
                'unsubscribeUrl' => $unsubscribeUrl,
            ]);
    }

    protected function hasUnsubscribeUrl(object $notifiable): bool
    {
        return method_exists($notifiable, 'getUnsubscribeUrl');
    }

    protected function generateUnsubscribeUrl(object $notifiable): ?string
    {
        return $notifiable->getUnsubscribeUrl($this);
    }
}
