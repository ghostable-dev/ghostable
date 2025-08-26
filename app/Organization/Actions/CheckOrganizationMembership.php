<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Cache;

class CheckOrganizationMembership extends OrganizationMembershipAction
{
    public function handle(User $user, Organization $organization): bool
    {
        return Cache::rememberForever(
            $this->cacheKeyForMembership(organization: $organization, user: $user),
            fn () => $organization->users()->where('user_id', $user->id)->exists()
        );
    }
}
