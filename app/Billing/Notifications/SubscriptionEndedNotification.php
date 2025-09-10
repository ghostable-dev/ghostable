<?php

namespace App\Billing\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionEndedNotification extends BillingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Subscription ended')
            ->greeting($notifiable->greeting())
            ->line("The Ghostable subscription for \"{$this->organization->name}\" has ended.")
            ->line('You are receiving this alert because you manage billing for this organization.');
    }
}
