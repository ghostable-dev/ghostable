<?php

use App\Account\Models\User;
use App\Messaging\Campaigns\Drip\InviteMembersNudge;
use App\Messaging\Campaigns\Drip\InviteMembersReminder;
use App\Messaging\Campaigns\Drip\Series\OnboardingSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('invite members nudge is eligible when user has an organization but no teammates or invites', function () {
    $user = User::factory()->create();

    $this->createOrganization('Acme Co', $user);

    $campaign = app(InviteMembersNudge::class);

    expect($campaign->eligible($user))->toBeTrue();
});

test('invite members nudge is not eligible once teammates join', function () {
    $user = User::factory()->create();
    $teammate = User::factory()->create();

    $this->createOrganization('Acme Co', $user, [$teammate]);

    $campaign = app(InviteMembersNudge::class);

    expect($campaign->eligible($user))->toBeFalse();
});

test('invite members nudge is not eligible when invites have been sent', function () {
    $user = User::factory()->create();

    $organization = $this->createOrganization('Acme Co', $user);

    $this->createInvite($organization, $user, 'jane@example.com');

    $campaign = app(InviteMembersNudge::class);

    expect($campaign->eligible($user))->toBeFalse();
});

test('onboarding series includes invite members step with reminder', function () {
    $series = OnboardingSeries::make();

    expect($series->steps)->toHaveCount(3);

    $inviteStep = $series->steps[2];

    expect($inviteStep->primary)->toBe(InviteMembersNudge::class)
        ->and($inviteStep->reminders)->toBe([InviteMembersReminder::class]);
});
