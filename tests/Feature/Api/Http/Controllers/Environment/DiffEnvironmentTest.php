<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('computes diff for authorized user', function () {
    $user = $this->createUser('Ray', 'ray@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $env,
        key: 'FOO',
        value: 'bar',
    ));

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/projects/{$project->id}/environments/{$env->name}/diff", [
        'vars' => ['FOO=baz', 'NEW=1'],
    ])
        ->assertOk()
        ->assertJsonPath('data.added.NEW.value', '1')
        ->assertJsonPath('data.updated.FOO.incoming.value', 'baz');
});

test('requires authentication to diff environments', function () {
    $user = $this->createUser('Ray', 'ray@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $this->postJson("/api/v1/projects/{$project->id}/environments/{$env->name}/diff", ['vars' => []])
        ->assertUnauthorized();
});

test('forbids users without permission', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $outsider = $this->createUser('Other', 'other@example.com');
    Sanctum::actingAs($outsider);

    $this->postJson("/api/v1/projects/{$project->id}/environments/{$env->name}/diff", ['vars' => []])
        ->assertForbidden();
});

test('returns 404 for missing environment', function () {
    $user = $this->createUser('Ray', 'ray@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Proj', $org);
    $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/projects/{$project->id}/environments/missing/diff", ['vars' => []])
        ->assertNotFound();
});
