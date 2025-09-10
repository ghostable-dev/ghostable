<?php

use App\Environment\Actions\UpdateBaseEnvironment;
use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('converts override to non-override when new base lacks variable', function () {
    $project = Project::factory()->create();

    $oldBase = Environment::factory()->forProject($project)->create();
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $oldBase,
        key: 'FOO',
        value: 'base',
    ));

    $env = Environment::factory()->forProject($project)->basedOn($oldBase)->create();

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $env,
        key: 'FOO',
        value: 'child',
        is_override: true,
    ));

    $newBase = Environment::factory()->forProject($project)->create();

    resolve(UpdateBaseEnvironment::class)->handle($env, $newBase);

    $var = $env->variables()->where('key', 'FOO')->first();

    expect((bool) $var->is_override)->toBeFalse();
});

it('removes tombstone when new base lacks variable', function () {
    $project = Project::factory()->create();

    $oldBase = Environment::factory()->forProject($project)->create();
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $oldBase,
        key: 'BAR',
        value: 'base',
    ));

    $env = Environment::factory()->forProject($project)->basedOn($oldBase)->create();

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $env,
        key: 'BAR',
        value: '',
        is_deleted: true,
    ));

    $newBase = Environment::factory()->forProject($project)->create();

    resolve(UpdateBaseEnvironment::class)->handle($env, $newBase);

    expect(EnvironmentVariable::query()->forEnvironment($env)->key('BAR')->exists())->toBeFalse();
});

it('removes local variable if new base provides same value', function () {
    $project = Project::factory()->create();

    $oldBase = Environment::factory()->forProject($project)->create();
    $env = Environment::factory()->forProject($project)->basedOn($oldBase)->create();

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $env,
        key: 'BAZ',
        value: 'same',
    ));

    $newBase = Environment::factory()->forProject($project)->create();
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $newBase,
        key: 'BAZ',
        value: 'same',
    ));

    resolve(UpdateBaseEnvironment::class)->handle($env, $newBase);

    expect(EnvironmentVariable::query()->forEnvironment($env)->key('BAZ')->exists())->toBeFalse();
});

it('marks local variable as override when new base provides different value', function () {
    $project = Project::factory()->create();

    $env = Environment::factory()->forProject($project)->create();
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $env,
        key: 'QUX',
        value: 'child',
    ));

    $newBase = Environment::factory()->forProject($project)->create();
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $newBase,
        key: 'QUX',
        value: 'base',
    ));

    resolve(UpdateBaseEnvironment::class)->handle($env, $newBase);

    $var = $env->variables()->where('key', 'QUX')->first();

    expect((bool) $var->is_override)->toBeTrue();
});
