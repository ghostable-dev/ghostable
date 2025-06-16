<?php

namespace App\Billing\Notifications;

use App\Core\Actions\GetDefaultMailMessage;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionEndedNotification extends BillingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        // $mailMessage = GetDefaultMailMessage::handle(
        //     unsubscribable: false,
        //     user: $notifiable
        // );

        // return $mailMessage
        //     ->subject('Membership Cancellation Confirmed')
        //     ->greeting($notifiable->greeting())
        //     ->line('We’re writing to inform you that we have received your request to cancel your membership with aijobs.com. After the end of your current billing cycle, your account will be downgraded to a Basic Member, and you will no longer be billed.')
        //     ->line('- Your account will be downgraded from a Premium Membership to a Basic Membership')
        //     ->line('- You will lose access to manage your team')
        //     ->line('- You will lose access to any currently running or scheduled job ads')
        //     ->line('- You will lose access to your branded career showcase page and custom hosted URL')
        //     ->line('You can re-activate your Premium Membership any time. If you have any questions or feedback, please don’t hesitate to contact our support team.')
        //     ->action('Reactivate Membership', route('account.settings.billing', $this->account))
        //     ->line('Thank you and we hope to see you again very soon!');
    }
}
