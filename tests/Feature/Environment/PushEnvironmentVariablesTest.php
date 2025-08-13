<?php

use App\Environment\Actions\PushEnvironmentVariables;
use App\Environment\Entities\PushEnvVarsStrategy;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Actions\SuppressInheritedVariable;
use App\Environment\Variable\Models\EnvironmentVariable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('reinstates suppressed inherited variable when re-added', function () {
    $project = $this->createProject('proj', $this->createTeam('team', $this->createUser('u', 'u@example.com')));
    $base = $this->createEnvironment('Base', EnvironmentType::PRODUCTION, $project);
    EnvironmentVariable::factory()->forEnvironment($base)->create([
        'key' => 'FOO',
        'value' => 'base',
    ]);
    $env = $this->createEnvironment('Child', EnvironmentType::DEVELOPMENT, $project, $base);

    resolve(SuppressInheritedVariable::class)->handle('FOO', $env);

    app(PushEnvironmentVariables::class)->handle($env, ['FOO=child'], new PushEnvVarsStrategy);

    $var = $env->variables()->where('key', 'FOO')->first();
    expect($var)->not->toBeNull();
    expect((bool) $var->is_deleted)->toBeFalse();
    expect($var->value)->toBe('child');
});

it('suppresses inherited variable when removed', function () {
    $project = $this->createProject('proj', $this->createTeam('team', $this->createUser('u', 'u@example.com')));
    $base = $this->createEnvironment('Base', EnvironmentType::PRODUCTION, $project);
    EnvironmentVariable::factory()->forEnvironment($base)->create([
        'key' => 'BAR',
        'value' => 'base',
    ]);
    $env = $this->createEnvironment('Child', EnvironmentType::DEVELOPMENT, $project, $base);

    app(PushEnvironmentVariables::class)->handle($env, [], new PushEnvVarsStrategy);
    $var = $env->variables()->where('key', 'BAR')->first();
    expect($var)->not->toBeNull();
    expect((bool) $var->is_deleted)->toBeTrue();
});

it('normalizes variable keys when pushing', function () {
    $project = $this->createProject('proj', $this->createTeam('team', $this->createUser('u', 'u@example.com')));
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    app(PushEnvironmentVariables::class)->handle($env, ['fooBar=baz'], new PushEnvVarsStrategy);

    $var = $env->variables()->where('key', 'FOOBAR')->first();
    expect($var)->not->toBeNull();
    expect($var->key)->toBe('FOOBAR');
});
