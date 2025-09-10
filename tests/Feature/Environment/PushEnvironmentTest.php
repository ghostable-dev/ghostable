<?php

use App\Environment\Actions\PushEnvironment;
use App\Environment\Entities\PushEnvironmentStrategy;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Enums\PushMode;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Actions\SuppressInheritedVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reinstates suppressed inherited variable when re-added', function () {
    $project = $this->createProject('proj', $this->createOrganization('organization', $this->createUser('u', 'u@example.com')));
    $base = $this->createEnvironment('Base', EnvironmentType::PRODUCTION, $project);
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $base,
        key: 'FOO',
        value: 'base',
    ));
    $env = $this->createEnvironment('Child', EnvironmentType::DEVELOPMENT, $project, $base);

    resolve(SuppressInheritedVariable::class)->handle('FOO', $env);

    app(PushEnvironment::class)->handle($env, ['FOO=child'], new PushEnvironmentStrategy);

    $var = $env->variables()->where('key', 'FOO')->first();
    expect($var)->not->toBeNull();
    expect((bool) $var->is_deleted)->toBeFalse();
    expect($var->value)->toBe('child');
});

it('suppresses inherited variable when removed with replace mode', function () {
    $project = $this->createProject('proj', $this->createOrganization('organization', $this->createUser('u', 'u@example.com')));
    $base = $this->createEnvironment('Base', EnvironmentType::PRODUCTION, $project);
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $base,
        key: 'BAR',
        value: 'base',
    ));
    $env = $this->createEnvironment('Child', EnvironmentType::DEVELOPMENT, $project, $base);

    app(PushEnvironment::class)->handle($env, [], new PushEnvironmentStrategy(mode: PushMode::REPLACE));
    $var = $env->variables()->where('key', 'BAR')->first();
    expect($var)->not->toBeNull();
    expect((bool) $var->is_deleted)->toBeTrue();
});

it('does not suppress inherited variable when missing in additive mode', function () {
    $project = $this->createProject('proj', $this->createOrganization('organization', $this->createUser('u', 'u@example.com')));
    $base = $this->createEnvironment('Base', EnvironmentType::PRODUCTION, $project);
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $base,
        key: 'BAR',
        value: 'base',
    ));
    $env = $this->createEnvironment('Child', EnvironmentType::DEVELOPMENT, $project, $base);

    app(PushEnvironment::class)->handle($env, [], new PushEnvironmentStrategy(mode: PushMode::ADDITIVE));

    $var = $env->variables()->where('key', 'BAR')->first();
    expect($var)->toBeNull();
});

it('normalizes variable keys when pushing', function () {
    $project = $this->createProject('proj', $this->createOrganization('organization', $this->createUser('u', 'u@example.com')));
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    app(PushEnvironment::class)->handle($env, ['fooBar=baz'], new PushEnvironmentStrategy);

    $var = $env->variables()->where('key', 'FOOBAR')->first();
    expect($var)->not->toBeNull();
    expect($var->key)->toBe('FOOBAR');
});
