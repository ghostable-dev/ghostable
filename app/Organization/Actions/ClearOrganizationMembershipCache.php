<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Cache;

class ClearOrganizationMembershipCache extends OrganizationMembershipAction
{
    public function handle(User $user, Organization $organization): void
    {
        Cache::forget($this->cacheKeyForMembership(organization: $organization, user: $user));
    }
}
