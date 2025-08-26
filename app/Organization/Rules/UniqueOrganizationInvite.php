<?php

namespace App\Organization\Rules;

use App\Organization\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueOrganizationInvite implements ValidationRule
{
    public function __construct(protected Organization $organization) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (DB::table('organization_invites')
            ->where('email', $value)
            ->where('organization_id', $this->organization->id)
            ->whereNull('deleted_at')
            ->exists()) {
            $fail('You already have a pending invite for this organization member.');
        }
    }
}
