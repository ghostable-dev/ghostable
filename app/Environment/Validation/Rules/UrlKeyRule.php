<?php

namespace App\Environment\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidatorAwareRule;

class UrlKeyRule extends KeyRule
{
    public function rule(): ValidatorAwareRule|string|array|Closure
    {
        return 'url';
    }
    
    public function message(): string
    {
        return "The :attribute must be a URL.";
    }
}