<?php

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;
use Tests\TestCase;

uses(TestCase::class);

it('builds rules with min and max', function () {
    $rule = new StringKeyRule(new RuleParameters(min: 1, max: 5));

    expect($rule->rule())->toBe(['string', 'min:1', 'max:5'])
        ->and($rule->message())->toBe('The :attribute field is required and must be a string between 1 and 5 characters long.');
});

it('builds rules with only min', function () {
    $rule = new StringKeyRule(new RuleParameters(min: 2));

    expect($rule->rule())->toBe(['string', 'min:2'])
        ->and($rule->message())->toBe('The :attribute field is required and must be a string at least 2 characters.');
});

it('builds rules with only max', function () {
    $rule = new StringKeyRule(new RuleParameters(max: 4));

    expect($rule->rule())->toBe(['string', 'max:4'])
        ->and($rule->message())->toBe('The :attribute field is required and must be a string no more than 4 characters.');
});

it('builds rules with no limits', function () {
    $rule = new StringKeyRule(new RuleParameters);

    expect($rule->rule())->toBe(['string'])
        ->and($rule->message())->toBe('The :attribute field is required and must be a string .');
});
