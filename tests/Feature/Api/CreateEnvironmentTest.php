<?php

use App\Environment\Enums\EnvironmentType;
use App\Organization\Enums\OrganizationRole;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated users cannot create environments', function () {
    $this->postJson('/api/v1/projects/123/environments')->assertUnauthorized();
});

test('persists a new environment record and returns JSON shape', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $ray);
    $project = $this->createProject(name: 'Website', organization: $organization);
    Sanctum::actingAs($ray);
    $payload = ['name' => 'staging', 'type' => EnvironmentType::STAGING->value];
    $this->postJson("/api/v1/projects/{$project->id}/environments", $payload)
        ->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'type',
                'base_id',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJsonPath('data.base_id', null);
    $env = $project->fresh()->environments()->where($payload)->first();
    $this->assertNotNull($env);
    expect($env->base_id)->toBeNull();
});

test('can set a base environment when creating', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $ray);
    $project = $this->createProject(name: 'Website', organization: $organization);
    $base = $this->createEnvironment(
        name: 'production',
        type: EnvironmentType::PRODUCTION,
        project: $project,
    );
    Sanctum::actingAs($ray);
    $payload = [
        'name' => 'staging',
        'type' => EnvironmentType::STAGING->value,
        'base_id' => $base->id,
    ];
    $this->postJson("/api/v1/projects/{$project->id}/environments", $payload)
        ->assertStatus(201)
        ->assertJsonPath('data.base_id', $base->id);
    $env = $project->fresh()->environments()->where('name', 'staging')->first();
    $this->assertNotNull($env);
    expect($env->base_id)->toBe($base->id);
});

test('zero-knowledge projects cannot set a base environment when creating', function () {
    $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $ray);
    $project = $this->createZeroKnowledgeProject(name: 'Website', organization: $organization);
    $base = $this->createEnvironment(
        name: 'production',
        type: EnvironmentType::PRODUCTION,
        project: $project,
    );

    Sanctum::actingAs($ray);

    $payload = [
        'name' => 'staging',
        'type' => EnvironmentType::STAGING->value,
        'base_id' => $base->id,
    ];

    $this->postJson("/api/v1/projects/{$project->id}/environments", $payload)
        ->assertStatus(422)
        ->assertJsonPath('error.fields.base_id.0', 'Base environments are not supported for this project.');

    $env = $project->fresh()->environments()->where('name', 'staging')->first();
    expect($env)->toBeNull();
});

describe('validation', function () {
    beforeEach(function () {
        $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
        $organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $ray);
        $this->project = $this->createProject(name: 'Website', organization: $organization);
        Sanctum::actingAs($ray);
        $this->endpoint = "/api/v1/projects/{$this->project->id}/environments";
    });

    test('fails when name is not a unique', function () {
        $existingEnv = $this->createEnvironment(
            name: 'website',
            type: EnvironmentType::DEVELOPMENT,
            project: $this->project
        );
        $this->postJson($this->endpoint, [
            'name' => $existingEnv->name,
            'type' => EnvironmentType::STAGING->value,
        ])->assertStatus(422);
    });

    test('fails when type is not a recognized organization role', function () {
        $this->postJson($this->endpoint, [
            'name' => 'Staging',
            'type' => 'invalid-type',
        ])->assertStatus(422);
    });
});

describe('authorization', function () {
    beforeEach(function () {
        $ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
        $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $ray);
        $this->project = $this->createProject(name: 'Website', organization: $this->organization);
        $this->zuul = $this->createUser(name: 'Zuul', email: 'zuul@gozers-minions.com');
        Sanctum::actingAs($ray);
        $this->endpoint = "/api/v1/projects/{$this->project->id}/environments";
    });

    test('forbids non-members from creating', function () {
        Sanctum::actingAs($this->zuul);
        $this->postJson($this->endpoint, [
            'name' => 'staging',
            'type' => EnvironmentType::STAGING->value,
        ])->assertForbidden();
    });

    test('forbids members without permission from creating', function () {
        $peter = $this->createUser(name: 'Peter', email: 'perter@ghostbusters.com');
        $peter->organizationMembership()->assignToOrganization(organization: $this->organization, role: OrganizationRole::DEVELOPER_READ_ONLY);
        Sanctum::actingAs($peter);
        $this->postJson($this->endpoint, [
            'name' => 'staging',
            'type' => EnvironmentType::STAGING->value,
        ])->assertForbidden();
    });
});
