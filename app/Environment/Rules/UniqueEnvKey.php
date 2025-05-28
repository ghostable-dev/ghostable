<?php

namespace App\Environment\Rules;

use App\Environment\Models\Environment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueEnvKey implements ValidationRule
{
    public function __construct(protected Environment $environment)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->environment->variables()->where('key', $value)->exists()) {
            $fail('The :attribute already exists in this environment.');
        }
    }
}