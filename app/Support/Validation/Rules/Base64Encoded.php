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

        $normalized = $value;

        if (str_starts_with($normalized, 'b64:')) {
            $normalized = substr($normalized, 4);
        } elseif (str_starts_with($normalized, 'base64:')) {
            $normalized = substr($normalized, 7);
        }

        $decoded = base64_decode($normalized, true);

        if ($decoded === false) {
            $fail('The :attribute must be a valid base64 string.');

            return;
        }

        if (base64_encode($decoded) !== rtrim($normalized, "\r\n")) {
            $fail('The :attribute must be a valid base64 string.');
        }
    }
}
