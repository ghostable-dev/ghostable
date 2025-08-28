<?php

namespace App\Billing\Http\Controllers;

use App\Billing\Enums\Plan;

class StandardCheckout extends SubscriptionCheckout
{
    protected function getBillablePlan(): Plan
    {
        return Plan::STANDARD;
    }
}
