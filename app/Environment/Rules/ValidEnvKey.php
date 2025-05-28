<?php

namespace App\Environment\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEnvKey implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $value)) {
            $fail('The :attribute must be uppercase, start with a letter, and contain only letters, numbers, and underscores.');
        }
    }
}