<?php

namespace App\Billing\Listeners;

use App\Account\Managers\ACLManager;
use App\Account\Models\Account;
use App\Account\Providers\ACLServiceProvider;
use App\Team\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

abstract class BillingNotificationListener implements ShouldQueue
{
    protected function notifiables(Team $team): Collection
    {
        return collect();
        // return $team->users()
        //     ->withRolesInAccount(
        //         $account,
        //         ACLManager::getRole(ACLServiceProvider::ROLE_ADMIN),
        //         ACLManager::getRole(ACLServiceProvider::ROLE_BILLING_MANAGER)
        //     )->get();
    }
}
