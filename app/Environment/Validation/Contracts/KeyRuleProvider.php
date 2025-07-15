<?php

namespace App\Environment\Validation\Contracts;

use Closure;
use Illuminate\Contracts\Validation\ValidatorAwareRule;

interface KeyRuleProvider
{
    /**
     * Get the validation rule.
     */
    public function rule(): ValidatorAwareRule|string|array|Closure;

    /**
     * Get the validation error message.
     */
    public function message(): string;
}
