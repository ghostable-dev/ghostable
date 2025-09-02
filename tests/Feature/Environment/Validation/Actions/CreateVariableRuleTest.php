<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Validation\Actions\CreateVariableRule;
use App\Environment\Validation\Actions\LogVariableRuleActivity;
use App\Environment\Validation\Entities\CreateVariableRuleData;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates environment variable rule and logs activity', function () {
    $user = $this->createUser('User', 'user@example.com');
    $organization = $this->createOrganization('Acme', $user);
    $project = $this->createProject('Example', $organization);
    $environment = $this->createEnvironment('staging', EnvironmentType::STAGING, $project);

    $data = new CreateVariableRuleData(
        environment: $environment,
        key: 'API_KEY',
        isRequired: true,
        type: EnvironmentVariableRuleType::STRING,
        min: 5,
        max: 10,
        allowedValues: ['abc', 'def'],
        description: 'My description',
        isOverride: true,
        isDeleted: false,
        createdBy: $user,
    );

    $logger = Mockery::mock(LogVariableRuleActivity::class);
    $logger->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(EnvironmentVariableRule::class), 'created', $user);
    app()->instance(LogVariableRuleActivity::class, $logger);

    $rule = app(CreateVariableRule::class)->handle($data);

    expect($rule)->toBeInstanceOf(EnvironmentVariableRule::class)
        ->and($rule->environment_id)->toBe($environment->id)
        ->and($rule->key)->toBe('API_KEY')
        ->and($rule->is_required)->toBeTrue()
        ->and($rule->type)->toBe(EnvironmentVariableRuleType::STRING)
        ->and($rule->min)->toBe(5)
        ->and($rule->max)->toBe(10)
        ->and($rule->allowed_values)->toBe(['abc', 'def'])
        ->and($rule->description)->toBe('My description')
        ->and($rule->is_override)->toBeTrue()
        ->and($rule->is_deleted)->toBeFalse();
});
