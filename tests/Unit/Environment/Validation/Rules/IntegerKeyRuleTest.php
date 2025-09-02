<?php

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\IntegerKeyRule;
use Tests\TestCase;

uses(TestCase::class);

it('builds rules with min and max', function () {
    $rule = new IntegerKeyRule(new RuleParameters(min: 1, max: 5));

    expect($rule->rule())->toBe(['integer', 'min:1', 'max:5'])
        ->and($rule->message())->toBe('The :attribute field is required and must be a integer between 1 and 5.');
});

it('builds rules with only min', function () {
    $rule = new IntegerKeyRule(new RuleParameters(min: 2));

    expect($rule->rule())->toBe(['integer', 'min:2'])
        ->and($rule->message())->toBe('The :attribute field is required and must be a integer at least 2.');
});

it('builds rules with only max', function () {
    $rule = new IntegerKeyRule(new RuleParameters(max: 4));

    expect($rule->rule())->toBe(['integer', 'max:4'])
        ->and($rule->message())->toBe('The :attribute field is required and must be a integer no greater than 4.');
});

it('builds rules with no limits', function () {
    $rule = new IntegerKeyRule(new RuleParameters);

    expect($rule->rule())->toBe(['integer'])
        ->and($rule->message())->toBe('The :attribute field is required and must be a integer .');
});
