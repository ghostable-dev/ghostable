<?php

namespace App\Billing\Http\Controllers;

class GrowthCheckout extends SubscriptionCheckout
{
    protected function getSubscriptionType(): ?string
    {
        return config('platform.billing.growth.type');
    }

    protected function getSubscriptionApiId(): ?string
    {
        return config('platform.billing.growth.api_id', null);
    }
}
