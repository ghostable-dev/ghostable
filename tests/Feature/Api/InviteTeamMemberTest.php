<?php

use App\Team\Enums\TeamRole;
use App\Team\Events\InviteCreated;
use App\Team\Notifications\TeamInviteNotification;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot invite team members', function () {
    $this->postJson('/api/v1/teams/123/invite')
        ->assertUnauthorized();
});

describe('validation', function () {
    beforeEach(function () {
        $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
        $this->peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
        $team = $this->createTeam(name: 'Ray’s Occult Books', owner: $ray, members: [$this->peter]);
        $this->endpoint = "/api/v1/teams/{$team->id}/invite";
        Sanctum::actingAs($ray);
    });

    test('fails when email is not a valid address', function () {
        $this->postJson($this->endpoint, [
            'email' => 'Egon',
            'role' => TeamRole::DEVELOPER->value,
        ])->assertStatus(422);
    });

    test('fails when role is not a recognized team role', function () {
        $this->postJson($this->endpoint, [
            'email' => 'egon@gmail.com',
            'role' => 'super-duper-admin',
        ])->assertStatus(422);
    });

    test('fails when inviting existing team member', function () {
        $this->postJson($this->endpoint, [
            'email' => $this->peter->email,
            'role' => TeamRole::DEVELOPER->value,
        ])->assertStatus(422);
    });
});

describe('authorization', function () {
    beforeEach(function () {
        $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
        $this->peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
        $this->zuul = $this->createUser(name: 'Zuul', email: 'zuul@gozers-minions.com');
        $team = $this->createTeam(name: 'Ray’s Occult Books', owner: $ray, members: [$this->peter]);
        $this->endpoint = "/api/v1/teams/{$team->id}/invite";
        $this->personalEndpoint = "/api/v1/teams/{$ray->personalTeam()->id}/invite";
        Sanctum::actingAs($ray);
    });

    test('forbids inviting on a personal team', function () {
        $this->postJson($this->personalEndpoint, [
            'email' => 'egon@gmail.com',
            'role' => TeamRole::DEVELOPER->value,
        ])->assertForbidden();
    });

    test('forbids non-members from inviting', function () {
        Sanctum::actingAs($this->zuul);
        $this->postJson($this->endpoint, [
            'email' => 'goozer@gozers-minions.com',
            'role' => TeamRole::ADMIN->value,
        ])->assertForbidden();
    });

    test('forbids non-admins from inviting', function () {
        Sanctum::actingAs($this->peter);
        $this->postJson($this->endpoint, [
            'email' => 'egon@gmail.com',
            'role' => TeamRole::DEVELOPER->value,
        ])->assertForbidden();
    });
});

test('team admin can invite a user by email', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $team = $this->createTeam(name: 'Ray’s Occult Books', owner: $ray);
    $payload = ['email' => 'egon@gmail.com', 'role' => TeamRole::DEVELOPER->value];
    Sanctum::actingAs($ray);

    Event::spy([InviteCreated::class]);
    Notification::fake();

    $this->postJson("/api/v1/teams/{$team->id}/invite", $payload)->assertStatus(200);

    $invite = $team->invites()->where($payload)->first();
    $this->assertNotNull($invite);

    Event::assertDispatched(InviteCreated::class, fn ($event) => $event->invite->id === $invite->id);

    Notification::assertSentTo($invite, TeamInviteNotification::class);
});
