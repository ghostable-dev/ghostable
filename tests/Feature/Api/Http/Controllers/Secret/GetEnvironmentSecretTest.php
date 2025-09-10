<?php

use App\Environment\Enums\EnvironmentType;
use App\Secret\Actions\CreateSecret;
use App\Secret\Enums\SecretType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('returns secret for authorized users', function () {
    $user = $this->createUser('Ray', 'ray@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $secret = app(CreateSecret::class)->handle(
        environment: $env,
        name: 'API_KEY',
        type: SecretType::TOKEN,
        value: 'initial',
        metadata: null,
        createdBy: $user,
    );

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/projects/{$project->id}/environments/{$env->name}/secrets/{$secret->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['id', 'name', 'type', 'value', 'created_at', 'updated_at'],
        ])
        ->assertJsonPath('data.id', $secret->id);
});

test('requires authentication to view secret', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $secret = app(CreateSecret::class)->handle(
        environment: $env,
        name: 'API_KEY',
        type: SecretType::TOKEN,
        value: 'initial',
        metadata: null,
        createdBy: $owner,
    );

    $this->getJson("/api/v1/projects/{$project->id}/environments/{$env->name}/secrets/{$secret->id}")
        ->assertUnauthorized();
});

test('forbids users without access', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $secret = app(CreateSecret::class)->handle(
        environment: $env,
        name: 'API_KEY',
        type: SecretType::TOKEN,
        value: 'initial',
        metadata: null,
        createdBy: $owner,
    );

    $outsider = $this->createUser('Other', 'other@example.com');
    Sanctum::actingAs($outsider);

    $this->getJson("/api/v1/projects/{$project->id}/environments/{$env->name}/secrets/{$secret->id}")
        ->assertForbidden();
});
