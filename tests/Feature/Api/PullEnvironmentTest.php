<?php

use App\Environment\Actions\RenderEnvFile;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Models\EnvironmentVariable;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->ray);
    $project = $this->createProject(name: 'Website', team: $this->team);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
    $this->env->file_format = EnvFileFormat::GROUPED;
    $this->env->save();
    $this->endpoint = "/api/projects/{$project->id}/environments/{$this->env->name}/pull";

    EnvironmentVariable::factory()->forEnvironment($this->env)->create([
        'key' => 'APP_NAME',
        'value' => 'Ghostable',
        'is_commented' => false,
    ]);
    EnvironmentVariable::factory()->forEnvironment($this->env)->create([
        'key' => 'DB_HOST',
        'value' => 'localhost',
        'is_commented' => false,
    ]);
});

test('unauthenticated users cannot pull environment', function () {
    $this->getJson($this->endpoint)->assertUnauthorized();
});

test('returns environment using default format when not specified', function () {
    Sanctum::actingAs($this->ray);
    $response = $this->get($this->endpoint);
    $expected = RenderEnvFile::handle(env: $this->env, format: EnvFileFormat::GROUPED);
    $response->assertOk();
    expect($response->getContent())->toBe($expected);
});

test('returns environment using provided format', function () {
    Sanctum::actingAs($this->ray);
    $response = $this->get("{$this->endpoint}?format=".EnvFileFormat::ALPHABETICAL->value);
    $expected = RenderEnvFile::handle(env: $this->env, format: EnvFileFormat::ALPHABETICAL);
    $response->assertOk();
    expect($response->getContent())->toBe($expected);
});

test('fails with invalid format parameter', function () {
    Sanctum::actingAs($this->ray);
    $this->getJson("{$this->endpoint}?format=invalid")->assertStatus(422);
});
