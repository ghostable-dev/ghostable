<?php

use App\Environment\Enums\EnvironmentType;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
    $project = $this->createProject(name: 'Website', organization: $this->organization);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
    $this->endpoint = "/api/v2/projects/{$project->id}/environments/{$this->env->name}/push";
});

test('push does not remove variables by default', function () {
    Sanctum::actingAs($this->ray);

    $this->postJson($this->endpoint, ['vars' => [
        'APP_DEBUG=TRUE',
        'APP_ENV=development',
        'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
        'APP_URL=https://www.raysoccultbooks.com',
        'CACHE_DRIVER=array',
    ]])->assertOk();

    $this->postJson($this->endpoint, ['vars' => [
        'APP_DEBUG=FALSE',
        'APP_ENV=development',
        'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
        'APP_URL=https://www.raysoccultbooks.com',
    ]])
        ->assertOk()
        ->assertJsonFragment(['removed' => 0]);

    $this->env->refresh();

    expect(
        $this->env->variables()->where('key', 'CACHE_DRIVER')->exists()
    )->toBeTrue();
});

test('push removes variables when sync is true', function () {
    Sanctum::actingAs($this->ray);

    $this->postJson($this->endpoint, ['vars' => [
        'APP_DEBUG=TRUE',
        'APP_ENV=development',
        'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
        'APP_URL=https://www.raysoccultbooks.com',
        'CACHE_DRIVER=array',
    ]])->assertOk();

    $this->postJson($this->endpoint, [
        'vars' => [
            'APP_DEBUG=FALSE',
            'APP_ENV=development',
            'APP_KEY=base64:bjlneWNjZmhyYmJqN2l6eWozaDNtdG1tdWZ1aHljZzU=',
            'APP_URL=https://www.raysoccultbooks.com',
        ],
        'sync' => true,
    ])
        ->assertOk()
        ->assertJsonFragment(['removed' => 1]);

    $this->env->refresh();

    expect(
        $this->env->variables()->where('key', 'CACHE_DRIVER')->exists()
    )->toBeFalse();
});
