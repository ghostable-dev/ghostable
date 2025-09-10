<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Cache;

class GetOrganizationMembershipRecord extends OrganizationMembershipAction
{
    public function handle(User $user, Organization $organization): ?Organization
    {
        return Cache::rememberForever(
            $this->cacheKeyForMembershipRecord(organization: $organization, user: $user),
            fn () => $user->organizations()->where('organization_id', $organization->id)->first()
        );
    }
}
