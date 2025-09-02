<?php

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Factories\KeyRuleProviderCollectionFactory;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use App\Environment\Validation\Rules\RequiredKeyRule;
use App\Environment\Validation\Rules\StringKeyRule;
use Tests\TestCase;

uses(TestCase::class);

it('builds providers including required rule when needed', function () {
    $factory = new KeyRuleProviderCollectionFactory;
    $factory->register(EnvironmentVariableRuleType::STRING, StringKeyRule::class);

    $rule = new EnvironmentVariableRule([
        'is_required' => true,
        'type' => EnvironmentVariableRuleType::STRING,
    ]);

    $providers = $factory->makeFromEnvironmentVariableRule($rule, new RuleParameters);

    expect($providers)->toHaveCount(2)
        ->and($providers[0])->toBeInstanceOf(RequiredKeyRule::class)
        ->and($providers[1])->toBeInstanceOf(StringKeyRule::class);
});

it('builds providers without required when rule is optional', function () {
    $factory = new KeyRuleProviderCollectionFactory;
    $factory->register(EnvironmentVariableRuleType::STRING, StringKeyRule::class);

    $rule = new EnvironmentVariableRule([
        'is_required' => false,
        'type' => EnvironmentVariableRuleType::STRING,
    ]);

    $providers = $factory->makeFromEnvironmentVariableRule($rule, new RuleParameters);

    expect($providers)->toHaveCount(1)
        ->and($providers[0])->toBeInstanceOf(StringKeyRule::class);
});

it('throws for unregistered rule types', function () {
    $factory = new KeyRuleProviderCollectionFactory;

    $rule = new EnvironmentVariableRule([
        'is_required' => false,
        'type' => EnvironmentVariableRuleType::STRING,
    ]);

    $factory->makeFromEnvironmentVariableRule($rule, new RuleParameters);
})->throws(InvalidArgumentException::class);
