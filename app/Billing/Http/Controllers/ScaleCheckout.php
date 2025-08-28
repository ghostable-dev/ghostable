<?php

namespace App\Billing\Http\Controllers;

use App\Billing\Enums\Plan;

class ScaleCheckout extends SubscriptionCheckout
{
    protected function getBillablePlan(): Plan
    {
        return Plan::SCALE;
    }
}
