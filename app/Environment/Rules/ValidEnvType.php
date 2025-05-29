<?php

namespace App\Environment\Rules;

use App\Environment\Enums\EnvironmentType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEnvType implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validValues = array_column(EnvironmentType::cases(), 'value');

        if (! is_string($value) || ! in_array($value, $validValues, strict: true)) {
            $fail("The selected :attribute [{$value}] is not a valid environment type.");
        }
    }
}