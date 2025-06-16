<?php

namespace App\Billing\Listeners;

use App\Billing\Events\SubscriptionEnded;
use App\Billing\Notifications\SubscriptionEndedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAccountOfEndedSubscription extends BillingNotificationListener
{
    public function handle(SubscriptionEnded $event): void
    {
        Notification::send(
            $this->notifiables($event->team),
            new SubscriptionEndedNotification($event->team)
        );
    }
}
