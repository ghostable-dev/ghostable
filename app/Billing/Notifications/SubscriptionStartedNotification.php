<?php

namespace App\Billing\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionStartedNotification extends BillingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Subscription activated')
            ->greeting($notifiable->greeting())
            ->line("The Ghostable subscription for \"{$this->organization->name}\" is now active.")
            ->line('You are receiving this alert because you manage billing for this organization.');
    }
}
