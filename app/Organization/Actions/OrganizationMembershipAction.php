<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Models\Organization;

abstract class OrganizationMembershipAction
{
    protected function cacheKeyForMembership(Organization $organization, User $user): string
    {
        return "organization:{$organization->id}:user:{$user->id}:belongs";
    }

    protected function cacheKeyForMembershipRecord(Organization $organization, User $user): string
    {
        return "organizationMembership:{$organization->id}:user:{$user->id}";
    }
}
