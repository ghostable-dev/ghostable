<?php

use App\Environment\Enums\EnvironmentType;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->ray);
    $this->project = $this->createProject(name: 'Website', team: $this->team);
    $this->local = $this->createEnvironment(name: 'local', type: EnvironmentType::LOCAL, project: $this->project);
    $this->dev = $this->createEnvironment(name: 'dev', type: EnvironmentType::DEVELOPMENT, project: $this->project);
    $otherProject = $this->createProject(name: 'Store', team: $this->team);
    $this->otherEnv = $this->createEnvironment(name: 'store', type: EnvironmentType::LOCAL, project: $otherProject);
    $this->endpoint = "/api/v1/projects/{$this->project->id}/environments";
});

test('unauthenticated users cannot fetch project environments', function () {
    $this->getJson($this->endpoint)->assertUnauthorized();
});

test('returns project environments for member user', function () {
    Sanctum::actingAs($this->ray);
    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $this->local->id])
        ->assertJsonMissing(['id' => $this->otherEnv->id]);
});

test('users cannot view project environments they do not belong to', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($peter);
    $this->getJson($this->endpoint)->assertForbidden();
});

test('returns project environments in correct structure', function () {
    Sanctum::actingAs($this->ray);
    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'type',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});
