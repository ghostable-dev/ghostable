<?php

namespace App\Billing\Concerns;

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
            get: fn () => collect([Plan::ENTERPRISE, Plan::GROWTH, Plan::STARTER])
                ->first(fn ($p) => $this->subscribed(type: $p))
                ?? Plan::FREE
        );
    }

    public function isEnterprise(): bool
    {
        return $this->subscribed(Plan::ENTERPRISE);
    }

    public function isGrowth(): bool
    {
        return $this->subscribed(Plan::GROWTH);
    }

    public function isStarter(): bool
    {
        return $this->subscribed(Plan::STARTER);
    }

    public function activeSubscription(): ?Subscription
    {
        if ($this->isEnterprise()) {
            return $this->subscription(Plan::ENTERPRISE);
        }

        if ($this->isGrowth()) {
            return $this->subscription(Plan::GROWTH);
        }

        if ($this->isStarter()) {
            return $this->subscription(Plan::STARTER);
        }

        return null;
    }
}
