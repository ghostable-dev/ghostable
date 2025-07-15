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

test('pushes vars for member user', function () {
    $vars = [
        'APP_ENV=production',
        'DB_HOST=127.0.0.1',
        'DB_PASSWORD=supersecret',
        '# COMMENTED_OUT=foo',
        'CACHE_DRIVER=file',
    ];
    Sanctum::actingAs($this->ray);
    $response = $this->postJson($this->endpoint, ['vars' => $vars]);
    $response->assertOk()->assertJsonFragment(['added' => 5, 'updated' => 0, 'removed' => 0]);

    $response = $this->postJson($this->endpoint, ['vars' => ['APP_ENV=staging']]);
    $response->assertOk()->assertJsonFragment(['added' => 0, 'updated' => 1, 'removed' => 4]);

    $response = $this->postJson($this->endpoint, ['vars' => ['APP_ENV=local']]);
    $response->assertOk()->assertJsonFragment(['added' => 0, 'updated' => 1, 'removed' => 0]);
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
    $this->postJson($this->endpoint, ['vars' => ['APP_ENV=local']])
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
