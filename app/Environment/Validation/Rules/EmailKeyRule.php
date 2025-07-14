<?php

namespace App\Environment\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Validation\Rules\Email;

class EmailKeyRule extends KeyRule
{
    public function rule(): ValidatorAwareRule|string|array|Closure
    {
        return new Email();
    }
    
    public function message(): string
    {
        return "The :attribute must be an email address.";
    }
}