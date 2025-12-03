<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Casey', 'casey@ghostable.com');
    $this->otherUser = $this->createUser('Morgan', 'morgan@ghostable.com');
});

test('unauthenticated users cannot list projects', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);

    $this->getJson("/api/v2/organizations/{$org->id}/projects")
        ->assertUnauthorized();
});

test('members can list projects in their organization', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->otherUser]);
    $first = $this->createProject('Containment Unit', $org);
    $second = $this->createProject('Slimer Capture', $org);

    Sanctum::actingAs($this->otherUser);
    $this->otherUser->refresh();
    $org->refresh();

    $response = $this->getJson("/api/v2/organizations/{$org->id}/projects");

    $response->assertOk();

    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toContain($first->name);
    expect($names)->toContain($second->name);
});

test('non-members cannot list projects', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);

    Sanctum::actingAs($this->otherUser);
    $this->otherUser->refresh();

    $this->getJson("/api/v2/organizations/{$org->id}/projects")
        ->assertForbidden();
});
