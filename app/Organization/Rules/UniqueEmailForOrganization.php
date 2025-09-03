<?php

namespace App\Organization\Rules;

use App\Organization\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueEmailForOrganization implements ValidationRule
{
    public function __construct(protected Organization $organization) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (DB::table('users')
            ->join('organization_user', 'users.id', '=', 'organization_user.user_id')
            ->where('users.email', $value)
            ->where('organization_user.organization_id', $this->organization->id)
            ->whereNull('organization_user.deleted_at')
            ->exists()) {
            $fail('The user already exists in this organization.');
        }
    }
}
