<?php

use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Invite;
use App\Organization\Policies\InvitePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('authorizes admins but not developers to manage invites', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $developer = $this->createUser('Dev', 'dev@example.com');
    $org = $this->createOrganization('Ghostbusters', $owner, [$developer]);
    Notification::fake();
    $invite = Invite::withoutEvents(fn () => Invite::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'email' => 'new@example.com',
        'role' => OrganizationRole::DEVELOPER,
    ]));

    $policy = new InvitePolicy;

    expect($policy->create($owner, $org))->toBeTrue()
        ->and($policy->delete($owner, $invite))->toBeTrue()
        ->and($policy->resend($owner, $invite))->toBeTrue()
        ->and($policy->create($developer, $org))->toBeFalse()
        ->and($policy->delete($developer, $invite))->toBeFalse()
        ->and($policy->resend($developer, $invite))->toBeFalse();
});

it('handles invite acceptance and decline rules', function () {
    $owner = $this->createUser('Owner', 'owner2@example.com');
    $org = $this->createOrganization('Spirits Inc', $owner);
    Notification::fake();

    $invitee = $this->createUser('Invitee', 'invitee@example.com');
    $invite = Invite::withoutEvents(fn () => Invite::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'email' => $invitee->email,
        'role' => OrganizationRole::DEVELOPER,
    ]));

    $policy = new InvitePolicy;

    expect($policy->accept($invitee, $invite))->toBeTrue()
        ->and($policy->decline($invitee, $invite))->toBeTrue();

    $mismatchUser = $this->createUser('Mismatch', 'mismatch@example.com');
    expect($policy->accept($mismatchUser, $invite))->toBeFalse()
        ->and($policy->decline($mismatchUser, $invite))->toBeFalse();

    $unverified = $this->createUser('Unverified', 'unverified@example.com');
    $unverified->email_verified_at = null;
    $unverified->save();
    $invite2 = Invite::withoutEvents(fn () => Invite::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'email' => $unverified->email,
        'role' => OrganizationRole::DEVELOPER,
    ]));

    expect($policy->accept($unverified, $invite2))->toBeFalse()
        ->and($policy->decline($unverified, $invite2))->toBeFalse();
});
