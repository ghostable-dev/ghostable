<?php

namespace App\Billing\Listeners;

use App\Organization\Models\Organization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

abstract class BillingNotificationListener implements ShouldQueue
{
    protected function notifiables(Organization $organization): Collection
    {
        return $organization->users;
    }
}
