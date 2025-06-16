<?php

namespace App\Billing\Http\Controllers;

class EnterpriseCheckout extends SubscriptionCheckout
{
    protected function getSubscriptionType(): ?string
    {
        return config('platform.billing.enterprise.type');
    }

    protected function getSubscriptionApiId(): ?string
    {
        return config('platform.billing.enterprise.api_id');
    }
}
