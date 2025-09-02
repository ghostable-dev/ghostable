<?php

use App\Organization\Enums\InviteStatus;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Invite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('filters invites by status', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    Notification::fake();

    $accepted = Invite::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'email' => 'a@example.com',
        'role' => OrganizationRole::DEVELOPER,
        'status' => InviteStatus::ACCEPTED,
    ]);
    $expired = Invite::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'email' => 'b@example.com',
        'role' => OrganizationRole::DEVELOPER,
        'status' => InviteStatus::EXPIRED,
    ]);
    $pending = Invite::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'email' => 'c@example.com',
        'role' => OrganizationRole::DEVELOPER,
    ]); // default pending

    expect(Invite::query()->accepted()->pluck('id'))->toContain($accepted->id)->not->toContain($expired->id);
    expect(Invite::query()->expired()->pluck('id'))->toContain($expired->id)->not->toContain($pending->id);
    expect(Invite::query()->pending()->pluck('id'))->toContain($pending->id)->not->toContain($accepted->id);
    expect(Invite::query()->withStatus(InviteStatus::ACCEPTED)->pluck('id'))->toContain($accepted->id);
});
