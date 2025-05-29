<?php

namespace App\Environment\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEnvType implements ValidationRule
{
    public function __construct(
        protected array $allowedTypes = []
    ) {
        if (empty($this->allowedTypes)) {
            $this->allowedTypes = config('ghostable.env_types', []);
        }
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! in_array($value, $this->allowedTypes, strict: true)) {
            $fail("The selected :attribute [{$value}] is invalid.");
        }
    }
}