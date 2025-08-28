<?php

namespace App\Billing\Concerns;

use App\Billing\Enums\Plan;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Laravel\Cashier\Billable as CashierBillable;
use Laravel\Cashier\Subscription;

trait Billable
{
    use CashierBillable;

    /**
     * Get the current plan (based on active subscription).
     */
    protected function plan(): Attribute
    {
        return Attribute::make(
            get: fn () => collect(Plan::billable())
                ->first(fn (Plan $plan) => $this->subscribed($plan->value))
                ?? Plan::FREE
        );
    }

    /**
     * Get the active subscription (based on plan).
     */
    public function activeSubscription(): ?Subscription
    {
        return collect(Plan::billable())
            ->map(fn (Plan $plan) => $this->subscription($plan->value))
            ->filter(fn (?Subscription $sub) => $sub?->valid())
            ->first();
    }
}
