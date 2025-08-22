<?php

use App\Environment\Actions\Token\CreateEnvToken;
use App\Environment\Enums\EnvironmentType;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $team = $this->createTeam(name: 'Ray\'s Occult Books', owner: $this->user);
    $project = $this->createProject(name: 'Website', team: $team);
    $this->environment = $this->createEnvironment(name: 'Production', type: EnvironmentType::PRODUCTION, project: $project);
    $this->endpoint = '/api/v1/ci/deploy';
});

test('unauthenticated users cannot deploy environments', function () {
    $this->get($this->endpoint)->assertUnauthorized();
});

test('environment token can deploy environment', function () {
    $token = app(CreateEnvToken::class)->handle(name: 'deploy', environment: $this->environment);

    $this->withHeaders(['Authorization' => 'Bearer '.$token->plainTextToken])
        ->get($this->endpoint)
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

test('user tokens cannot deploy environments', function () {
    $userToken = $this->user->createToken('test');

    $this->withHeaders(['Authorization' => 'Bearer '.$userToken->plainTextToken])
        ->get($this->endpoint)
        ->assertForbidden();
});
