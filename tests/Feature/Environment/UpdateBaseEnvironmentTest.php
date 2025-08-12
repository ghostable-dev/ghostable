<?php

use App\Environment\Actions\UpdateBaseEnvironment;
use App\Environment\Models\Environment;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('converts override to non-override when new base lacks variable', function () {
    $project = Project::factory()->create();

    $oldBase = Environment::factory()->forProject($project)->create();
    EnvironmentVariable::factory()->forEnvironment($oldBase)->create([
        'key' => 'FOO',
        'value' => 'base',
    ]);

    $env = Environment::factory()->forProject($project)->basedOn($oldBase)->create();

    EnvironmentVariable::factory()->forEnvironment($env)->create([
        'key' => 'FOO',
        'value' => 'child',
        'is_override' => true,
    ]);

    $newBase = Environment::factory()->forProject($project)->create();

    resolve(UpdateBaseEnvironment::class)->handle($env, $newBase);

    $var = $env->variables()->where('key', 'FOO')->first();

    expect((bool) $var->is_override)->toBeFalse();
});

it('removes tombstone when new base lacks variable', function () {
    $project = Project::factory()->create();

    $oldBase = Environment::factory()->forProject($project)->create();
    EnvironmentVariable::factory()->forEnvironment($oldBase)->create([
        'key' => 'BAR',
        'value' => 'base',
    ]);

    $env = Environment::factory()->forProject($project)->basedOn($oldBase)->create();

    EnvironmentVariable::factory()->forEnvironment($env)->create([
        'key' => 'BAR',
        'value' => '',
        'is_deleted' => true,
    ]);

    $newBase = Environment::factory()->forProject($project)->create();

    resolve(UpdateBaseEnvironment::class)->handle($env, $newBase);

    expect(EnvironmentVariable::query()->forEnvironment($env)->key('BAR')->exists())->toBeFalse();
});

it('removes local variable if new base provides same value', function () {
    $project = Project::factory()->create();

    $oldBase = Environment::factory()->forProject($project)->create();
    $env = Environment::factory()->forProject($project)->basedOn($oldBase)->create();

    EnvironmentVariable::factory()->forEnvironment($env)->create([
        'key' => 'BAZ',
        'value' => 'same',
    ]);

    $newBase = Environment::factory()->forProject($project)->create();
    EnvironmentVariable::factory()->forEnvironment($newBase)->create([
        'key' => 'BAZ',
        'value' => 'same',
    ]);

    resolve(UpdateBaseEnvironment::class)->handle($env, $newBase);

    expect(EnvironmentVariable::query()->forEnvironment($env)->key('BAZ')->exists())->toBeFalse();
});
