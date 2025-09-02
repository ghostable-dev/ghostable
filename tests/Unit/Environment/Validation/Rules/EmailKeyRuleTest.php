<?php

use App\Environment\Validation\Rules\EmailKeyRule;
use Illuminate\Validation\Rules\Email;
use Tests\TestCase;

uses(TestCase::class);

test('email key rule', function () {
    $rule = new EmailKeyRule;

    expect($rule->rule())->toBeInstanceOf(Email::class)
        ->and($rule->message())->toBe('The :attribute must be an email address.');
});
