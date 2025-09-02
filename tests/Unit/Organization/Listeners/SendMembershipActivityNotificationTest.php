<?php

use App\Organization\Events\InviteAccepted;
use App\Organization\Events\InviteCreated;
use App\Organization\Events\MemberRemoved;
use App\Organization\Listeners\SendMembershipActivityNotification;
use App\Organization\Models\Invite;
use App\Organization\Notifications\MemberInvitedNotification;
use App\Organization\Notifications\MemberJoinedNotification;
use App\Organization\Notifications\MemberRemovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

class FakeListener extends SendMembershipActivityNotification
{
    public function __construct(private bool $enabled, private Collection $recipients) {}

    public array $sent = [];

    protected function isNotificationEnabled($organization, $key): bool
    {
        return $this->enabled;
    }

    protected function getOrganizationRecipients($organization): Collection
    {
        return $this->recipients;
    }

    protected function sendNotification($recipient, $notification): void
    {
        $this->sent[] = [$recipient, $notification];
    }
}

it('does nothing when notifications are disabled', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Ghostbusters', $owner);
    Notification::fake();
    $invite = Invite::withoutEvents(fn () => Invite::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'email' => 'new@example.com',
    ]));
    $event = new InviteCreated($invite);

    $listener = new FakeListener(false, collect());
    $listener->handle($event);

    expect($listener->sent)->toBe([]);
});

it('sends invited notification', function () {
    $owner = $this->createUser('Owner', 'owner2@example.com');
    $recipient = $this->createUser('Rec', 'rec@example.com');
    $org = $this->createOrganization('Org', $owner, [$recipient]);
    Notification::fake();
    $invite = Invite::withoutEvents(fn () => Invite::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'email' => 'new2@example.com',
    ]));
    $event = new InviteCreated($invite);

    $listener = new FakeListener(true, collect([$recipient]));
    $listener->handle($event);

    expect($listener->sent)->toHaveCount(1)
        ->and($listener->sent[0][1])->toBeInstanceOf(MemberInvitedNotification::class);
});

it('sends joined notification', function () {
    $owner = $this->createUser('Owner3', 'owner3@example.com');
    $recipient = $this->createUser('Rec3', 'rec3@example.com');
    $org = $this->createOrganization('Org3', $owner, [$recipient]);
    Notification::fake();
    $invite = Invite::withoutEvents(fn () => Invite::create([
        'organization_id' => $org->id,
        'user_id' => $owner->id,
        'email' => 'join@example.com',
    ]));
    $event = new InviteAccepted($invite);

    $listener = new FakeListener(true, collect([$recipient]));
    $listener->handle($event);

    expect($listener->sent)->toHaveCount(1)
        ->and($listener->sent[0][1])->toBeInstanceOf(MemberJoinedNotification::class);
});

it('sends member removed notification', function () {
    $owner = $this->createUser('Owner4', 'owner4@example.com');
    $recipient = $this->createUser('Rec4', 'rec4@example.com');
    $removed = $this->createUser('Removed', 'removed@example.com');
    $org = $this->createOrganization('Org4', $owner, [$recipient, $removed]);
    Notification::fake();
    $event = new MemberRemoved($org, $removed);

    $listener = new FakeListener(true, collect([$recipient]));
    $listener->handle($event);

    expect($listener->sent)->toHaveCount(1)
        ->and($listener->sent[0][1])->toBeInstanceOf(MemberRemovedNotification::class);
});
