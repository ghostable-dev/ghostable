<?php

namespace App\Billing;

use App\Billing\Listeners\StripeWebhookListener;
use App\Team\Models\Team;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;

class BillingServiceProvider extends ServiceProvider
{
    public const BUSINESS = 'business';

    public const ENTERPRISE = 'enterprise';

    public function boot(): void
    {
        Cashier::useCustomerModel(Team::class);

        // Event::listen(SubscriptionStarted::class, NotifyAccountOfStartedSubscription::class);
        // Event::listen(SubscriptionEnded::class, NotifyAccountOfEndedSubscription::class);
        Event::listen(WebhookHandled::class, StripeWebhookListener::class);
    }
}
