<?php

use App\Environment\Enums\EnvironmentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('returns secrets for authorized users', function () {
    $user = $this->createUser('Ray', 'ray@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $this->createSecrets($env, $user, 2);

    Sanctum::actingAs($user);

    $this->getJson("/api/v1/projects/{$project->id}/environments/{$env->name}/secrets")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('requires authentication to list secrets', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $this->getJson("/api/v1/projects/{$project->id}/environments/{$env->name}/secrets")
        ->assertUnauthorized();
});

test('forbids users without access', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $outsider = $this->createUser('Other', 'other@example.com');
    Sanctum::actingAs($outsider);

    $this->getJson("/api/v1/projects/{$project->id}/environments/{$env->name}/secrets")
        ->assertForbidden();
});
