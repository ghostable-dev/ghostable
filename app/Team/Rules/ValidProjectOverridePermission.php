<?php

namespace App\Team\Rules;

use App\Team\Enums\TeamRole;
use App\Team\Enums\TeamPermission;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidProjectOverridePermission implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof TeamPermission) {
            return;
        }

        // Otherwise, check string against valid values
        $validValues = array_map(fn (TeamPermission $role) => $role->value, TeamPermission::projectOverrides());
        if (! in_array($value, $validValues, true)) {
            $fail("The selected :attribute [{$value}] is not a valid project override permission.");
        }
    }
}
