<?php

use App\Account\Models\User;
use App\Team\Actions\CreateTeam;
use App\Team\Enums\TeamRole;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('team admin can invite a user by email', function () {
    Notification::fake();

    $user = User::factory()->create();
    $team = CreateTeam::handle('Acme', $user);

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/teams/{$team->id}/invite", [
        'email' => 'invite@example.com',
        'role' => TeamRole::DEVELOPER->value,
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Invitation sent.']);
});

test('inviting existing team member fails', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $team = CreateTeam::handle('Acme', $owner);

    $member = User::factory()->create(['email' => 'member@example.com']);
    $member->teamMembership()->assignToTeam(team: $team, role: TeamRole::DEVELOPER);

    Sanctum::actingAs($owner);

    $response = $this->postJson("/api/teams/{$team->id}/invite", [
        'email' => 'member@example.com',
        'role' => TeamRole::DEVELOPER->value,
    ]);

    $response->assertStatus(422);
});
