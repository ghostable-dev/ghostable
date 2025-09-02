<?php

use App\Organization\Actions\OrganizationMembershipAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('organization membership action builds cache keys', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner);

    $action = new class extends OrganizationMembershipAction
    {
        public function membershipKey($org, $user)
        {
            return $this->cacheKeyForMembership($org, $user);
        }

        public function recordKey($org, $user)
        {
            return $this->cacheKeyForMembershipRecord($org, $user);
        }
    };

    expect($action->membershipKey($organization, $owner))
        ->toBe("organization:{$organization->id}:user:{$owner->id}:belongs")
        ->and($action->recordKey($organization, $owner))
        ->toBe("organizationMembership:{$organization->id}:user:{$owner->id}");
});
