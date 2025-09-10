<?php

use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->ray);
    $project = $this->createProject(name: 'Website', organization: $this->organization);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
    $this->env->file_format = EnvFileFormat::GROUPED;
    $this->env->save();
    $this->endpoint = "/api/v1/projects/{$project->id}/environments/{$this->env->name}/fetch";

    $this->appName = app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->env,
        key: 'APP_NAME',
        value: 'Ghostable',
    ));
    $this->dbHost = app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->env,
        key: 'DB_HOST',
        value: 'localhost',
    ));
});

test('unauthenticated users cannot pull environment', function () {
    $this->getJson($this->endpoint)->assertUnauthorized();
});

test('returns environment vars for given environment', function () {
    Sanctum::actingAs($this->ray);

    $response = $this->get($this->endpoint);

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $this->appName->id, 'key' => 'APP_NAME'])
        ->assertJsonFragment(['id' => $this->dbHost->id, 'key' => 'DB_HOST']);
});

test('returns environment variables in correct structure', function () {
    Sanctum::actingAs($this->ray);
    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'key',
                    'value',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});
