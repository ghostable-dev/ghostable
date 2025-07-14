<?php

namespace App\Environment\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Rule;

class BooleanKeyRule extends KeyRule
{
    public function rule(): ValidatorAwareRule|string|array|Closure
    {
        return Rule::in(['true', 'false', 'TRUE', 'FALSE', '1', '0']);
    }
    
    public function message(): string
    {
        return "The :attribute must be either TRUE or FALSE.";
    }
}