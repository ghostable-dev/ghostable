<?php

namespace App\Organization\Rules;

use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WithinOrganizationUserCap implements ValidationRule
{
    public function __construct(protected Organization $organization) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $role = OrganizationRole::tryFrom($value);

        if (! $role) {
            return;
        }

        // Billing and auditor roles do not count toward user limits
        if (in_array($role, [OrganizationRole::BILLING_ONLY, OrganizationRole::AUDITOR], true)) {
            return;
        }

        $cap = $this->organization->limits->users;

        if ($cap === null) {
            return;
        }

        $currentUsers = $this->organization->users()
            ->wherePivot('role', '!=', OrganizationRole::BILLING_ONLY->value)
            ->wherePivot('role', '!=', OrganizationRole::AUDITOR->value)
            ->count();

        $pendingInvites = $this->organization->invites()
            ->pending()
            ->whereNotIn('role', [OrganizationRole::BILLING_ONLY->value, OrganizationRole::AUDITOR->value])
            ->count();

        if (($currentUsers + $pendingInvites + 1) > $cap) {
            $fail('User limit reached for this organization.');
        }
    }
}

