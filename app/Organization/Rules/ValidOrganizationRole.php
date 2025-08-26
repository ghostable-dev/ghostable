<?php

namespace App\Organization\Rules;

use App\Organization\Enums\OrganizationRole;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidOrganizationRole implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof OrganizationRole) {
            return;
        }

        // Otherwise, check string against valid values
        $validValues = array_map(fn (OrganizationRole $role) => $role->value, OrganizationRole::cases());
        if (! in_array($value, $validValues, true)) {
            $fail("The selected :attribute [{$value}] is not a valid role.");
        }
    }
}
