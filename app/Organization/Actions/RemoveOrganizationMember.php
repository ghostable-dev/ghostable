<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Events\MemberRemoved;
use App\Organization\Models\Organization;

class RemoveOrganizationMember
{
    public function handle(User $member, Organization $organization): Organization
    {
        $member->organizationMembership()->removeFromOrganization($organization);

        MemberRemoved::dispatch($organization, $member);

        return $organization;
    }
}
