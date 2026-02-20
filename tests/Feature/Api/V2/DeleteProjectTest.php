<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->owner = $this->createUser('Dana', 'dana@example.com');
    $this->member = $this->createUser('Louis', 'louis@example.com');
});

test('unauthenticated users cannot delete a project', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->owner);
    $project = $this->createProject('Containment Unit', $org);

    $this->deleteJson("/api/v2/projects/{$project->id}")
        ->assertUnauthorized();
});

test('organization owners can delete projects', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->owner);
    $project = $this->createProject('Containment Unit', $org);

    Sanctum::actingAs($this->owner);

    $this->deleteJson("/api/v2/projects/{$project->id}")
        ->assertNoContent();

    $this->assertSoftDeleted('projects', [
        'id' => $project->id,
    ]);
});

test('members without delete permission cannot delete projects', function (): void {
    $org = $this->createOrganization('Ghostbusters', $this->owner, [$this->member]);
    $project = $this->createProject('Containment Unit', $org);

    Sanctum::actingAs($this->member);

    $this->deleteJson("/api/v2/projects/{$project->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'deleted_at' => null,
    ]);
});
