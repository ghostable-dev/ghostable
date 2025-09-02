<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Validation\Actions\DeleteVariableRule;
use App\Environment\Validation\Actions\LogVariableRuleActivity;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('deletes environment variable rule and logs activity', function () {
    $user = $this->createUser('User', 'user@example.com');
    $organization = $this->createOrganization('Acme', $user);
    $project = $this->createProject('Example', $organization);
    $environment = $this->createEnvironment('staging', EnvironmentType::STAGING, $project);

    $rule = new EnvironmentVariableRule([
        'key' => 'API_KEY',
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
        'min' => null,
        'max' => null,
        'allowed_values' => [],
        'description' => null,
        'is_override' => false,
        'is_deleted' => false,
    ]);
    $rule->environment()->associate($environment);
    $rule->save();

    expect(EnvironmentVariableRule::count())->toBe(1);

    $logger = Mockery::mock(LogVariableRuleActivity::class);
    $logger->shouldReceive('handle')
        ->once()
        ->with(Mockery::type(EnvironmentVariableRule::class), 'deleted', $user);
    app()->instance(LogVariableRuleActivity::class, $logger);

    app(DeleteVariableRule::class)->handle($rule, $user);

    expect(EnvironmentVariableRule::count())->toBe(0);
});
