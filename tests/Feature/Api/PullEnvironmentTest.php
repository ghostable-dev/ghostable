<?php

use App\Environment\Actions\RenderEnvFile;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->ray);
    $project = $this->createProject(name: 'Website', team: $this->team);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
    $this->env->file_format = EnvFileFormat::GROUPED;
    $this->env->save();
    $this->endpoint = "/api/v1/projects/{$project->id}/environments/{$this->env->name}/pull";

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->env,
        key: 'APP_NAME',
        value: 'Ghostable',
    ));
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->env,
        key: 'DB_HOST',
        value: 'localhost',
    ));
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
