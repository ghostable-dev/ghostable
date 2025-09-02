<?php

use App\Organization\Enums\OrganizationRole;
use App\Organization\Livewire\OrganizationMembersManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('organization member role can be updated', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $member = $this->createUser('Member', 'member@example.com');
    $organization = $this->createOrganization('Ghostbusters', $owner, [$member]);

    $this->actingAs($owner);

    Livewire::test(OrganizationMembersManager::class)
        ->call('manageMemberRole', $member->id)
        ->set('managingRole', OrganizationRole::ADMIN->value)
        ->call('saveMemberRole');

    $member->organizationMembership()->clearMembershipCache($organization);
    $roleInDb = DB::table('organization_user')
        ->where('organization_id', $organization->id)
        ->where('user_id', $member->id)
        ->value('role');

    expect($roleInDb)->toBe(OrganizationRole::ADMIN->value);
});
