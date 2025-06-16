<?php

namespace App\Billing\Http\Controllers;

use App\Billing\Http\Controllers\SubscriptionCheckout;

class BusinessCheckout extends SubscriptionCheckout
{
    protected function getSubscriptionType(): ?string
    {
        return config('platform.billing.business.type');
    }
    
    protected function getSubscriptionApiId(): ?string
    {
        return config('platform.billing.business.api_id', null);
    }
}
