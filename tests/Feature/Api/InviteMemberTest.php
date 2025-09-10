<?php

use App\Organization\Enums\OrganizationRole;
use App\Organization\Events\InviteCreated;
use App\Organization\Notifications\InviteNotification;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot invite organization members', function () {
    $this->postJson('/api/v1/organizations/123/invite')
        ->assertUnauthorized();
});

describe('validation', function () {
    beforeEach(function () {
        $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
        $this->peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
        $organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $ray, members: [$this->peter]);
        $this->endpoint = "/api/v1/organizations/{$organization->id}/invite";
        Sanctum::actingAs($ray);
    });

    test('fails when email is not a valid address', function () {
        $this->postJson($this->endpoint, [
            'email' => 'Egon',
            'role' => OrganizationRole::DEVELOPER->value,
        ])->assertStatus(422);
    });

    test('fails when role is not a recognized organization role', function () {
        $this->postJson($this->endpoint, [
            'email' => 'egon@gmail.com',
            'role' => 'super-duper-admin',
        ])->assertStatus(422);
    });

    test('fails when inviting existing organization member', function () {
        $this->postJson($this->endpoint, [
            'email' => $this->peter->email,
            'role' => OrganizationRole::DEVELOPER->value,
        ])->assertStatus(422);
    });
});

describe('authorization', function () {
    beforeEach(function () {
        $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
        $this->peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
        $this->zuul = $this->createUser(name: 'Zuul', email: 'zuul@gozers-minions.com');
        $organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $ray, members: [$this->peter]);
        $this->endpoint = "/api/v1/organizations/{$organization->id}/invite";
        Sanctum::actingAs($ray);
    });

    test('forbids non-members from inviting', function () {
        Sanctum::actingAs($this->zuul);
        $this->postJson($this->endpoint, [
            'email' => 'goozer@gozers-minions.com',
            'role' => OrganizationRole::ADMIN->value,
        ])->assertForbidden();
    });

    test('forbids non-admins from inviting', function () {
        Sanctum::actingAs($this->peter);
        $this->postJson($this->endpoint, [
            'email' => 'egon@gmail.com',
            'role' => OrganizationRole::DEVELOPER->value,
        ])->assertForbidden();
    });
});

test('organization admin can invite a user by email', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $ray);
    $payload = ['email' => 'egon@gmail.com', 'role' => OrganizationRole::DEVELOPER->value];
    Sanctum::actingAs($ray);

    Event::spy([InviteCreated::class]);
    Notification::fake();

    $this->postJson("/api/v1/organizations/{$organization->id}/invite", $payload)->assertStatus(200);

    $invite = $organization->invites()->where($payload)->first();
    $this->assertNotNull($invite);

    Event::assertDispatched(InviteCreated::class, fn ($event) => $event->invite->id === $invite->id);

    Notification::assertSentTo($invite, InviteNotification::class);
});

test('cannot invite beyond plan user limit excluding billing and auditor roles', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $member = $this->createUser('Member', 'member@example.com');
    $organization = $this->createOrganization('Ghostbusters', $owner);
    $member->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER);

    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/organizations/{$organization->id}/invite", [
        'email' => 'extra@example.com',
        'role' => OrganizationRole::DEVELOPER->value,
    ])
        ->assertStatus(422)
        ->assertJsonPath('error.fields.role.0', 'User limit reached for this organization.');
});

test('billing or auditor invites are allowed even when limit reached', function () {
    $owner = $this->createUser('Owner', 'owner2@example.com');
    $member = $this->createUser('Member', 'member2@example.com');
    $organization = $this->createOrganization('Spirits Inc', $owner);
    $member->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER);

    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/organizations/{$organization->id}/invite", [
        'email' => 'bill@example.com',
        'role' => OrganizationRole::BILLING_ONLY->value,
    ])->assertStatus(200);

    $this->postJson("/api/v1/organizations/{$organization->id}/invite", [
        'email' => 'auditor@example.com',
        'role' => OrganizationRole::AUDITOR->value,
    ])->assertStatus(200);
});

test('existing billing members do not count toward user limit', function () {
    $owner = $this->createUser('Owner', 'owner3@example.com');
    $billing = $this->createUser('Bill', 'bill@example.com');
    $organization = $this->createOrganization('Haunted Org', $owner);
    $billing->organizationMembership()->assignToOrganization($organization, OrganizationRole::BILLING_ONLY);

    Sanctum::actingAs($owner);

    $this->postJson("/api/v1/organizations/{$organization->id}/invite", [
        'email' => 'dev@example.com',
        'role' => OrganizationRole::DEVELOPER->value,
    ])->assertStatus(200);
});
