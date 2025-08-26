<?php

namespace App\Billing\Http\Controllers;

class StarterCheckout extends SubscriptionCheckout
{
    protected function getSubscriptionType(): ?string
    {
        return config('platform.billing.starter.type');
    }

    protected function getSubscriptionApiId(): ?string
    {
        return config('platform.billing.starter.api_id', null);
    }
}
