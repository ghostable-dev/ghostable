<?php

namespace App\Billing\Concerns;

use App\Billing\BillingServiceProvider;
use App\Billing\Enums\Plan;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Cashier\Billable as CashierBillable;
use Laravel\Cashier\Subscription;

trait Billable
{
    use CashierBillable;

    protected function plan(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->subscribed(type: BillingServiceProvider::ENTERPRISE)) {
                    return Plan::ENTERPRISE;
                } elseif ($this->subscribed(type: BillingServiceProvider::BUSINESS)) {
                    return Plan::BUSINESS;
                } else {
                    return Plan::PERSONAL;
                }
            }
        );
    }

    public function isEnterprise(): bool
    {
        return $this->subscribed(BillingServiceProvider::ENTERPRISE);
    }

    public function isBusiness(): bool
    {
        return $this->subscribed(BillingServiceProvider::BUSINESS);
    }

    public function activeSubscription(): ?Subscription
    {
        if ($this->isEnterprise()) {
            return $this->subscription(BillingServiceProvider::ENTERPRISE);
        }

        if ($this->isBusiness()) {
            return $this->subscription(BillingServiceProvider::BUSINESS);
        }

        return null;
    }
}
