<?php

use App\Environment\Models\Environment;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates parameters from model', function () {
    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create();

    $rule = $env->rules()->create([
        'key' => 'FOO',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
        'min' => 1,
        'max' => 3,
        'allowed_values' => ['a', 'b'],
    ]);

    $params = RuleParameters::fromEnvironmentVariableRule($rule);

    expect($params->min)->toBe(1)
        ->and($params->max)->toBe(3)
        ->and($params->allowedValues)->toBe(['a', 'b']);
});

it('can be instantiated directly', function () {
    $params = new RuleParameters(min: 2, max: 5, allowedValues: ['x']);

    expect($params->min)->toBe(2)
        ->and($params->max)->toBe(5)
        ->and($params->allowedValues)->toBe(['x']);
});
