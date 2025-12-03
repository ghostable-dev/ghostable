<?php

use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Dana', 'dana@ghostable.com');
    $this->member = $this->createUser('Louis', 'louis@ghostable.com');
});

test('unauthenticated users cannot create a project', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);

    $this->postJson("/api/v2/organizations/{$org->id}/projects", [
        'name' => 'Containment Unit',
    ])->assertUnauthorized();
});

test('members can create a project under their organization', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user, [$this->member]);

    Sanctum::actingAs($this->member);
    $this->member->refresh();
    $org->refresh();

    $payload = [
        'name' => 'Containment Unit',
        'description' => 'Ghost storage',
    ];

    $response = $this->postJson("/api/v2/organizations/{$org->id}/projects", $payload);

    $response->assertCreated();

    $data = $response->json('data') ?? [];

    expect(data_get($data, 'name'))->toBe($payload['name']);
    expect(data_get($data, 'organization_id'))->toBe($org->id);

    $this->assertDatabaseHas('projects', [
        'name' => $payload['name'],
        'organization_id' => $org->id,
    ]);

    $project = Project::query()->where('name', $payload['name'])->firstOrFail();

    expect($project->environments()->count())->toBeGreaterThanOrEqual(1);
});

test('non-members cannot create a project', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->user);

    Sanctum::actingAs($this->member);

    $this->postJson("/api/v2/organizations/{$org->id}/projects", [
        'name' => 'Unauthorized Project',
    ])->assertForbidden();
});
