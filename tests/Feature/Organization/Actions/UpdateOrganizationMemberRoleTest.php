<?php

use App\Organization\Actions\UpdateOrganizationMemberRole;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Events\MemberRoleChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;

uses(RefreshDatabase::class);

test('update organization member role changes role', function () {
    Event::fake();

    $owner = $this->createUser('Owner', 'owner@example.com');
    $member = $this->createUser('Member', 'member@example.com');
    $organization = $this->createOrganization('Acme', $owner, [$member]);

    UpdateOrganizationMemberRole::handle($member, $organization, OrganizationRole::ADMIN);

    $pivotRole = $member->organizations()->where('organization_id', $organization->id)->first()->pivot->role;
    expect($pivotRole)->toBe(OrganizationRole::ADMIN);
    Event::assertDispatched(MemberRoleChanged::class);
});

test('updating role for non-member throws', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner);
    $user = $this->createUser('User', 'user@example.com');

    UpdateOrganizationMemberRole::handle($user, $organization, OrganizationRole::ADMIN);
})->throws(RuntimeException::class);
