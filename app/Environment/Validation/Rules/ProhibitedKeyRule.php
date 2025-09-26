<?php

namespace App\Environment\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Rule;

class ProhibitedKeyRule extends KeyRule
{
    public function rule(): ValidatorAwareRule|string|array|Closure
    {
        return Rule::prohibitedIf(fn () => true);
    }

    public function message(): string
    {
        return 'The :attribute must is a prohibited key for this environment.';
    }
}
