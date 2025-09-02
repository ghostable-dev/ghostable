<?php

use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use Tests\TestCase;

uses(TestCase::class);

it('provides select options mapping', function () {
    $options = EnvironmentVariableRuleType::selectOptions();

    expect($options['string'])->toBe('String')
        ->and($options['boolean'])->toBe('True / False');
});

it('returns human labels', function () {
    expect(EnvironmentVariableRuleType::INTEGER->label())->toBe('Number');
});
