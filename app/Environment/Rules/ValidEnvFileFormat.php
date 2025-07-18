<?php

namespace App\Environment\Rules;

use App\Environment\Enums\EnvFileFormat;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEnvFileFormat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validValues = array_column(EnvFileFormat::cases(), 'value');

        $value = $value instanceof EnvFileFormat ? $value->value : $value;

        if (! is_string($value) || ! in_array($value, $validValues, true)) {
            $fail("The selected :attribute [{$value}] is not a valid environment file format.");
        }
    }
}
