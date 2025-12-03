<?php

use App\Environment\Enums\EnvironmentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Pat', 'pat@ghostable.com');
    $this->member = $this->createUser('Alex', 'alex@ghostable.com');
});

test('unauthenticated users cannot list environments', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);

    $this->getJson("/api/v2/projects/{$project->id}/environments")
        ->assertUnauthorized();
});

test('members can list environments for their project', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);

    $prod = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);
    $stage = $this->createEnvironment('staging', EnvironmentType::STAGING, $project, $prod);

    Sanctum::actingAs($this->member);
    $this->member->refresh();
    $project->refresh();

    $response = $this->getJson("/api/v2/projects/{$project->id}/environments");

    $response->assertOk();

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain($prod->name);
    expect($names)->toContain($stage->name);
});

test('non-members cannot list environments', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);

    Sanctum::actingAs($this->member);

    $this->getJson("/api/v2/projects/{$project->id}/environments")
        ->assertForbidden();
});
