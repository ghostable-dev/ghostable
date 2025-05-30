<?php

namespace App\Team\Rules;

use App\Team\Enums\TeamRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTeamRole implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof TeamRole) {
            return;
        }

        // Otherwise, check string against valid values
        $validValues = array_map(fn (TeamRole $role) => $role->value, TeamRole::cases());
        if (! in_array($value, $validValues, true)) {
            $fail("The selected :attribute [{$value}] is not a valid role.");
        }
    }
}
