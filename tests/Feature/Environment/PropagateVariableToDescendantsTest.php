<?php

use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks descendant variables as overrides when ancestor adds same key', function () {
    $project = Project::factory()->create();

    $root = Environment::factory()->forProject($project)->create();
    $child = Environment::factory()->forProject($project)->basedOn($root)->create();
    $grandchild = Environment::factory()->forProject($project)->basedOn($child)->create();

    EnvironmentVariable::factory()->forEnvironment($child)->create([
        'key' => 'FOO',
        'value' => 'child',
    ]);

    EnvironmentVariable::factory()->forEnvironment($grandchild)->create([
        'key' => 'FOO',
        'value' => 'grandchild',
    ]);

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $root,
        key: 'FOO',
        value: 'root',
    ));

    $childVar = EnvironmentVariable::query()->forEnvironment($child)->key('FOO')->first();
    $grandVar = EnvironmentVariable::query()->forEnvironment($grandchild)->key('FOO')->first();

    expect((bool) $childVar->is_override)->toBeTrue()
        ->and((bool) $grandVar->is_override)->toBeTrue();
});

it('removes identical descendant variable when ancestor adds same key', function () {
    $project = Project::factory()->create();

    $root = Environment::factory()->forProject($project)->create();
    $child = Environment::factory()->forProject($project)->basedOn($root)->create();

    EnvironmentVariable::factory()->forEnvironment($child)->create([
        'key' => 'BAR',
        'value' => 'same',
    ]);

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $root,
        key: 'BAR',
        value: 'same',
    ));

    expect(EnvironmentVariable::query()->forEnvironment($child)->key('BAR')->exists())
        ->toBeFalse();
});
