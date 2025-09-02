<?php

use App\Environment\Models\Environment;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Rules\VariableRuleFormRules;
use App\Environment\Variable\Rules\ValidVariableKey;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\Unique;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('builds create rules', function () {
    $project = Project::factory()->create();
    $environment = Environment::factory()->forProject($project)->create();

    $rules = VariableRuleFormRules::createRules($environment);

    expect($rules)->toHaveKeys([
        'key', 'is_required', 'type', 'min', 'max',
        'allowed_values', 'allowed_values.*', 'description',
    ]);

    expect($rules['key'][0])->toBe('required')
        ->and($rules['key'][1])->toBeInstanceOf(ValidVariableKey::class)
        ->and($rules['key'][2])->toBeInstanceOf(Unique::class);

    expect($rules['min'])->toBe(['nullable', 'integer', 'min:0'])
        ->and($rules['max'])->toBe(['nullable', 'integer', 'min:0', 'gte:min']);
});

it('builds update rules', function () {
    $project = Project::factory()->create();
    $environment = Environment::factory()->forProject($project)->create();
    $rule = $environment->rules()->create([
        'key' => 'FOO',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
    ]);

    $rules = VariableRuleFormRules::updateRules($rule);

    expect($rules['key'][0])->toBe('required')
        ->and($rules['key'][1])->toBeInstanceOf(ValidVariableKey::class)
        ->and($rules['key'][2])->toBeInstanceOf(Unique::class);
});
