<?php

use App\Environment\Actions\PushEnvironment;
use App\Environment\Enums\EnvironmentType;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->ray);
    $project = $this->createProject(name: 'Website', team: $this->team);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
    $this->endpoint = "/api/projects/{$project->id}/environments/{$this->env->name}/validate";
});

test('unauthenticated users cannot get validate environments', function () {
    $this->getJson($this->endpoint)->assertUnauthorized();
});

test('returns ok with valid environment', function () {
    app(PushEnvironment::class)->handle($this->env, [
        'APP_DEBUG=TRUE',
        'APP_ENV=development',
        'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
        'APP_URL=https://www.raysoccultbooks.com',
    ]);
    Sanctum::actingAs($this->ray);
    $response = $this->getJson($this->endpoint);
    $response->assertOk()->assertOk();
});

test('users cannot view validate environments they are not members of', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($peter);
    $this->getJson($this->endpoint)->assertForbidden();
});

test('returns errors with invalid environment', function () {
    app(PushEnvironment::class)->handle($this->env, [
        'APP_DEBUG=TRUE',
        'APP_ENV=development',
        'APP_URL=https://www.raysoccultbooks.com',
    ]);
    Sanctum::actingAs($this->ray);
    $response = $this->getJson($this->endpoint);
    $response->assertStatus(422);
});
