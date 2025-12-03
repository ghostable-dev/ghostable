<?php

use App\Environment\Enums\EnvironmentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Egon', 'egon@ghostable.com');
    $this->member = $this->createUser('Peter', 'peter@ghostable.com');
});

test('unauthenticated users cannot create environments', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);

    $this->postJson("/api/v2/projects/{$project->id}/environments", [
        'name' => 'production',
        'type' => EnvironmentType::PRODUCTION->value,
    ])->assertUnauthorized();
});

test('members can create environments for their project', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);

    Sanctum::actingAs($this->member);
    $this->member->refresh();
    $project->refresh();

    $payload = [
        'name' => 'production',
        'type' => EnvironmentType::PRODUCTION->value,
    ];

    $response = $this->postJson("/api/v2/projects/{$project->id}/environments", $payload);

    $response->assertCreated();

    $data = $response->json('data') ?? [];
    expect(data_get($data, 'name'))->toBe($payload['name']);
    expect(data_get($data, 'type'))->toBe($payload['type']);

    $this->assertDatabaseHas('environments', [
        'name' => $payload['name'],
        'project_id' => $project->id,
    ]);
});

test('non-members cannot create environments', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);
    $project = $this->createProject('Containment Unit', $org);

    Sanctum::actingAs($this->member);

    $this->postJson("/api/v2/projects/{$project->id}/environments", [
        'name' => 'staging',
        'type' => EnvironmentType::STAGING->value,
    ])->assertForbidden();
});
