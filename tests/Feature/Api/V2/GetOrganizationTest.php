<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Ava', 'ava@ghostable.com');
    $this->otherUser = $this->createUser('Sam', 'sam@ghostable.com');
});

test('unauthenticated users cannot fetch an organization', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);

    $this->getJson("/api/v2/organizations/{$org->id}")
        ->assertUnauthorized();
});

test('returns organization details for members', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->otherUser]);

    Sanctum::actingAs($this->otherUser);
    $this->otherUser->refresh();

    $response = $this->getJson("/api/v2/organizations/{$org->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $org->id)
        ->assertJsonPath('data.name', $org->name);
});

test('non-members cannot access the organization', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);

    Sanctum::actingAs($this->otherUser);

    $this->getJson("/api/v2/organizations/{$org->id}")
        ->assertForbidden();
});
