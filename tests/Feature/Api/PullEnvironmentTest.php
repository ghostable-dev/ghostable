<?php

use App\Environment\Actions\CreateEnvVariable;
use App\Environment\Entities\CreateEnvVariableData;
use App\Environment\Enums\EnvironmentType;
use App\Team\Enums\TeamRole;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->ray = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->ray);
    $project = $this->createProject(name: 'Website', team: $this->team);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);

    app(CreateEnvVariable::class)->handle(new CreateEnvVariableData(
        environment: $this->env, key: 'APP_NAME', value: 'Ray’s Occult', createdBy: $this->ray
    ));

    app(CreateEnvVariable::class)->handle(new CreateEnvVariableData(
        environment: $this->env, key: 'APP_DEBUG', value: 'TRUE', createdBy: $this->ray
    ));

    app(CreateEnvVariable::class)->handle(new CreateEnvVariableData(
        environment: $this->env, key: 'APP_ENV', value: 'development', createdBy: $this->ray
    ));

    $this->endpoint = "/api/projects/{$project->id}/environments/{$this->env->name}/pull";
});

test('unauthenticated users cannot pull environments', function () {
    $this->getJson($this->endpoint)->assertUnauthorized();
});

test('pulls vars for member user', function () {
    Sanctum::actingAs($this->ray);
    $response = $this->getJson($this->endpoint);
    $response->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $expected = implode("\n", [
        'APP_DEBUG=TRUE',
        'APP_ENV=development',
        'APP_NAME="Ray’s Occult"',
    ]);
    $this->assertEquals($expected, $response->getContent());
});

test('forbids non-members from pulling', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    Sanctum::actingAs($peter);
    $this->getJson($this->endpoint)->assertForbidden();
});

test('forbids members without permission from pulling', function () {
    $peter = $this->createUser(name: 'Peter', email: 'peter@ghostbusters.com');
    $peter->teamMembership()->assignToTeam(team: $this->team, role: TeamRole::BILLING_ONLY);
    Sanctum::actingAs($peter);
    $this->getJson($this->endpoint)->assertForbidden();
});
