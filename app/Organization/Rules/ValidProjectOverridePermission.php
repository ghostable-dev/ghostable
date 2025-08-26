<?php

namespace App\Organization\Rules;

use App\Organization\Enums\OrganizationPermission;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidProjectOverridePermission implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof OrganizationPermission) {
            return;
        }

        // Otherwise, check string against valid values
        $validValues = array_map(fn (OrganizationPermission $role) => $role->value, OrganizationPermission::projectOverrides());
        if (! in_array($value, $validValues, true)) {
            $fail("The selected :attribute [{$value}] is not a valid project override permission.");
        }
    }
}
