<?php

use App\Organization\Actions\AcceptInvite;
use App\Organization\Actions\CreateInvite;
use App\Organization\Enums\InviteStatus;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Events\InviteAccepted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('user can accept an invite', function () {
    Event::fake();

    $owner = $this->createUser('Owner', 'owner@example.com');
    $invitee = $this->createUser('Invitee', 'invitee@example.com');
    $organization = $this->createOrganization('Acme', $owner);
    $invite = CreateInvite::handle($organization, $owner, $invitee->email, OrganizationRole::DEVELOPER);

    app(AcceptInvite::class)->handle($invitee, $invite);

    $invitee = $invitee->fresh();

    expect($invitee->organizations)->toHaveCount(1)
        ->and($invitee->organizations->first()->pivot->role)->toBe(OrganizationRole::DEVELOPER)
        ->and($invite->fresh()->status)->toBe(InviteStatus::ACCEPTED);

    Event::assertDispatched(InviteAccepted::class);
});

test('suspended users cannot accept an invite', function () {
    Event::fake();

    $owner = $this->createUser('Owner', 'owner@example.com');
    $invitee = $this->createUser('Invitee', 'invitee@example.com');
    $invitee->suspend();

    $organization = $this->createOrganization('Acme', $owner);
    $invite = CreateInvite::handle($organization, $owner, $invitee->email, OrganizationRole::DEVELOPER);

    app(AcceptInvite::class)->handle($invitee, $invite);

    $invitee = $invitee->fresh();

    expect($invitee->organizations)->toHaveCount(0)
        ->and($invite->fresh()->status)->toBe(InviteStatus::PENDING);

    Event::assertNotDispatched(InviteAccepted::class);
});

test('locked users cannot accept an invite', function () {
    Event::fake();

    $owner = $this->createUser('Owner', 'owner@example.com');
    $invitee = $this->createUser('Invitee', 'invitee@example.com');
    $invitee->lock();

    $organization = $this->createOrganization('Acme', $owner);
    $invite = CreateInvite::handle($organization, $owner, $invitee->email, OrganizationRole::DEVELOPER);

    app(AcceptInvite::class)->handle($invitee, $invite);

    $invitee = $invitee->fresh();

    expect($invitee->organizations)->toHaveCount(0)
        ->and($invite->fresh()->status)->toBe(InviteStatus::PENDING);

    Event::assertNotDispatched(InviteAccepted::class);
});
