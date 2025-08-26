<?php

namespace App\Core\Actions;

use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Illuminate\Support\Collection;

class GetNotifiableOrganizationUsers
{
    public static function handle(Organization $organization): Collection
    {
        return $organization->users()
            ->wherePivot('role', '!=', OrganizationRole::BILLING_ONLY)
            ->get();
    }
}
