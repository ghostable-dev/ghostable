<?php

use App\Environment\Validation\Rules\RequiredKeyRule;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Definitions\DatabaseConnection;
use Tests\TestCase;

uses(TestCase::class);

it('defines database connection variable', function () {
    $def = new DatabaseConnection;

    expect($def->key())->toBe('DB_CONNECTION')
        ->and($def->suggestedValues())->toBe(['mysql', 'pgsql', 'sqlite'])
        ->and($def->requiredProvider())->toBeInstanceOf(RequiredKeyRule::class)
        ->and($def->ruleProviders()[0])->toBeInstanceOf(StringKeyRule::class);
});
