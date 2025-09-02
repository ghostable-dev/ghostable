<?php

use App\Environment\Models\Environment;
use App\Environment\Resolvers\EnvironmentAncestryResolver;
use App\Environment\Validation\Actions\ResolveEnvironmentVariableRules;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('resolves inherited and overriding rules', function () {
    $project = Project::factory()->create();
    $base = Environment::factory()->forProject($project)->create(['name' => 'Base']);
    $derived = Environment::factory()->forProject($project)->basedOn($base)->create(['name' => 'Derived']);

    $base->rules()->create([
        'key' => 'FOO',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
    ]);

    $base->rules()->create([
        'key' => 'BAZ',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
    ]);

    $derived->rules()->create([
        'key' => 'FOO',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
    ]);

    $action = new ResolveEnvironmentVariableRules(new EnvironmentAncestryResolver);

    $resolved = $action->handle($derived);

    expect($resolved)->toHaveCount(2);

    $foo = $resolved->firstWhere('key', 'FOO');
    $baz = $resolved->firstWhere('key', 'BAZ');

    expect($foo->inherited)->toBeFalse()
        ->and($foo->overrides)->toBeTrue()
        ->and($foo->origin)->toBe('Derived');

    expect($baz->inherited)->toBeTrue()
        ->and($baz->overrides)->toBeFalse()
        ->and($baz->origin)->toBe('Base');
});
