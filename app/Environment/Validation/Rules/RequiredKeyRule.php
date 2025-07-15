<?php

namespace App\Environment\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidatorAwareRule;

class RequiredKeyRule extends KeyRule
{
    public function rule(): ValidatorAwareRule|string|array|Closure
    {
        return 'required';
    }

    public function message(): string
    {
        return 'The :attribute key is required but missing from the environment.';
    }
}
