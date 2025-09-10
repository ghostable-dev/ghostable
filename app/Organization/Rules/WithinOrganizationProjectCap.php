<?php

namespace App\Organization\Rules;

use App\Organization\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WithinOrganizationProjectCap implements ValidationRule
{
    public function __construct(protected Organization $organization) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // $cap = $this->organization->limits->projects;

        // if ($cap !== null && $this->organization->projects()->count() >= $cap) {
        //     $fail('Project limit reached for this organization.');
        // }
    }
}
