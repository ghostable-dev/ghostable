<?php

namespace App\Billing\Notifications;

use App\Core\Actions\GetDefaultMailMessage;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionStartedNotification extends BillingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        // $mailMessage = GetDefaultMailMessage::handle(
        //     unsubscribable: false,
        //     user: $notifiable
        // );

        // return $mailMessage
        //     ->subject('Welcome to the ai jobs community!')
        //     ->greeting($notifiable->greeting())
        //     ->line('Today, we welcome you as an official member of our ai jobs community!')
        //     ->line('As a Premium Member, you’ll have the ability to publish your AI job ads on our platform, while also gaining access to a variety of tools to help with matchmaking your AI job to AI talent.')
        //     ->line('Let’s get started by posting your ai jobs so we can start matchmaking them to AI talent.')
        //     ->action('Post Jobs', route('account.jobs', $this->account))
        //     ->line('If you have questions getting started or need any help, reach out to our support team.');;
    }
}
