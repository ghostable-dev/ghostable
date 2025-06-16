<?php

namespace App\Billing\Listeners;

use App\Billing\Events\SubscriptionStarted;
use App\Billing\Listeners\BillingNotificationListener;
use App\Billing\Notifications\SubscriptionStartedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAccountOfStartedSubscription extends BillingNotificationListener
{
    public function handle(SubscriptionStarted $event): void
    {
        Notification::send(
            $this->notifiables($event->team),
            new SubscriptionStartedNotification($event->team)
        );
    }
}
