<?php

declare(strict_types=1);

namespace App\Support\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class Base64Encoded implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid base64 string.');

            return;
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            $fail('The :attribute must be a valid base64 string.');

            return;
        }

        if (base64_encode($decoded) !== rtrim($value, "\r\n")) {
            $fail('The :attribute must be a valid base64 string.');
        }
    }
}
