<?php

namespace App\Environment\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Rule;

class EnumKeyRule extends KeyRule
{
    public function rule(): ValidatorAwareRule|string|array|Closure
    {
        return Rule::in($this->parameters->allowedValues);
    }
    
    public function message(): string
    {
        return sprintf(
            "The :attribute must be one of: %s.", 
            implode(', ', $this->parameters->allowedValues)
        );
    }
}