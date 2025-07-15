<?php

use App\Environment\Enums\EnvironmentType;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->ray);
    $project = $this->createProject(name: 'Website', team: $this->team);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
    $this->endpoint = "/api/projects/{$project->id}/environments/{$this->env->name}";
});

test('unauthenticated users cannot get environments', function () {
    $this->getJson($this->endpoint)->assertUnauthorized();
});

test('returns environment for member user', function () {
    Sanctum::actingAs($this->ray);
    $response = $this->getJson($this->endpoint);
    $response->assertOk()->assertJsonFragment(['id' => $this->env->id]);
});

test('users cannot view environments they are not members of', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($peter);
    $this->getJson($this->endpoint)->assertForbidden();
});

test('returns environment in the correct format', function () {
    Sanctum::actingAs($this->ray);
    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'type',
                'created_at',
                'updated_at'
            ],
        ]);
});