<?php

use App\Environment\Enums\EnvironmentType;
use App\Team\Enums\TeamRole;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->ray);
    $project = $this->createProject(name: 'Website', team: $this->team);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
    $this->endpoint = "/api/projects/{$project->id}/environments/{$this->env->name}/push";
});

test('unauthenticated users cannot push environments', function () {
    $this->postJson($this->endpoint)->assertUnauthorized();
});

test('fails with improper vars', function () {
    Sanctum::actingAs($this->ray);
    $this->postJson($this->endpoint, ['vars' => ['APP_ENV=local']])->assertStatus(422);
});

test('fails with production debug true', function () {
    $project = $this->createProject(name: 'Store', team: $this->team);
    $production = $this->createEnvironment(name: 'Production', type: EnvironmentType::PRODUCTION, project: $project);
    Sanctum::actingAs($this->ray);
    $this->postJson("/api/projects/{$project->id}/environments/{$production->name}/push", ['vars' => [
        'APP_DEBUG=TRUE',
        'APP_ENV=production',
        'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
        'APP_URL=https://www.raysoccultbooks.com',
    ]])->assertStatus(422);
});

test('pushes vars for member user', function () {
    Sanctum::actingAs($this->ray);
    $response = $this->postJson($this->endpoint, ['vars' => [
        'APP_DEBUG=TRUE',
        'APP_ENV=development',
        'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
        'APP_URL=https://www.raysoccultbooks.com',
    ]]);
    $response->assertOk()->assertJsonFragment(['added' => 4, 'updated' => 0, 'removed' => 0]);

    $response = $this->postJson($this->endpoint, ['vars' => [
        'APP_DEBUG=FALSE',
        'APP_ENV=development',
        'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
        'APP_URL=https://www.raysoccultbooks.com',
        'CACHE_DRIVER=array',
    ]]);
    $response->assertOk()->assertJsonFragment(['added' => 1, 'updated' => 1, 'removed' => 0]);

    $response = $this->postJson($this->endpoint, ['vars' => [
        'APP_DEBUG=TRUE',
        'APP_ENV=development',
        'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
        'APP_URL=https://www.raysoccultbooks.com',
    ]]);
    $response->assertOk()->assertJsonFragment(['added' => 0, 'updated' => 1, 'removed' => 1]);
});

test('forbids non-members from pushing', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($peter);
    $this->postJson($this->endpoint, ['vars' => ['APP_ENV=local']])->assertForbidden();
});

test('forbids members without permission from pushing', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    $peter->teamMembership()->assignToTeam(team: $this->team, role: TeamRole::DEVELOPER_READ_ONLY);
    Sanctum::actingAs($peter);
    $this->postJson($this->endpoint, ['vars' => ['APP_ENV=local']])->assertForbidden();
});

test('returns push result in the correct format', function () {
    Sanctum::actingAs($this->ray);
    $this->postJson($this->endpoint, ['vars' => [
        'APP_DEBUG=TRUE',
        'APP_ENV=development',
        'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
        'APP_URL=https://www.raysoccultbooks.com',
    ]])
        ->assertOk()
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'updated',
                'added',
                'removed',
            ],
        ]);
});
