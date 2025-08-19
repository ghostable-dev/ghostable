<?php

use App\Environment\Enums\EnvironmentType;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->ray);
    $project = $this->createProject(name: 'Website', team: $this->team);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
    $this->endpoint = "/api/v1/projects/{$project->id}/environments/{$this->env->name}/validate";
});

test('unauthenticated users cannot validate environments', function () {
    $this->postJson($this->endpoint, ['vars' => []])->assertUnauthorized();
});

test('returns ok with valid environment', function () {
    $vars = [
        'APP_DEBUG=TRUE',
        'APP_ENV=development',
        'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
        'APP_URL=https://www.raysoccultbooks.com',
    ];

    Sanctum::actingAs($this->ray);
    $response = $this->postJson($this->endpoint, ['vars' => $vars]);
    $response->assertOk();
});

test('users cannot validate environments they are not members of', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($peter);
    $this->postJson($this->endpoint, ['vars' => []])->assertForbidden();
});

test('returns errors with invalid environment', function () {
    $vars = [
        'APP_DEBUG=TRUE',
        'APP_ENV=development',
        'APP_URL=https://www.raysoccultbooks.com',
    ];

    Sanctum::actingAs($this->ray);
    $response = $this->postJson($this->endpoint, ['vars' => $vars]);
    $response->assertStatus(422);
});
