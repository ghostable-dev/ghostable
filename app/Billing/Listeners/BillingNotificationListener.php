<?php

namespace App\Billing\Listeners;

use App\Account\Managers\ACLManager;
use App\Account\Providers\ACLServiceProvider;
use App\Organization\Models\Organization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

abstract class BillingNotificationListener implements ShouldQueue
{
    protected function notifiables(Organization $organization): Collection
    {
        return collect();
        // return $organization->users()
        //     ->withRolesInAccount(
        //         $account,
        //         ACLManager::getRole(ACLServiceProvider::ROLE_ADMIN),
        //         ACLManager::getRole(ACLServiceProvider::ROLE_BILLING_MANAGER)
        //     )->get();
    }
}
