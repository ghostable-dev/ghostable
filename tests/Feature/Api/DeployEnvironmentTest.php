<?php

use App\Environment\Actions\Token\CreateEnvToken;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $organization = $this->createOrganization(name: 'Ray\'s Occult Books', owner: $this->user);
    $project = $this->createProject(name: 'Website', organization: $organization);
    $this->environment = $this->createEnvironment(name: 'Production', type: EnvironmentType::PRODUCTION, project: $project);
    $this->endpoint = '/api/v1/ci/deploy';
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'APP_NAME',
        value: 'Ghostable',
    ));
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'APP_DEBUG',
        value: 'false',
    ));
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'APP_URL',
        value: 'https://ghostable.dev',
    ));
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'APP_ENV',
        value: 'production',
    ));
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'APP_KEY',
        value: 'base64:dTk5YXdvZ3V5bDdrZTRnNm1sbHk2YzI1NW0zcnNmcWg=',
    ));
});

test('unauthenticated users cannot deploy environments', function () {
    $this->get($this->endpoint)->assertUnauthorized();
});

test('environment token can deploy environment', function () {
    $token = app(CreateEnvToken::class)
        ->handle(name: 'deploy', environment: $this->environment);

    $this->withHeaders(['Authorization' => 'Bearer '.$token->plainTextToken])
        ->get($this->endpoint)
        ->assertOk();
});

test('user tokens cannot deploy environments', function () {
    $userToken = $this->user->createToken('test');

    $this->withHeaders(['Authorization' => 'Bearer '.$userToken->plainTextToken])
        ->get($this->endpoint)
        ->assertForbidden();
});
