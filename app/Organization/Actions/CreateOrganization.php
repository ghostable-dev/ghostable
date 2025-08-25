<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;

class CreateOrganization
{
    public static function handle(string $name, User $owner, bool $personal = false): Organization
    {
        $organization = new Organization;
        $organization->name = $name;
        $organization->owner()->associate($owner);
        $organization->is_personal = $personal;
        $organization->save();

        $owner->organizationMembership()->assignToOrganization(organization: $organization, role: OrganizationRole::ADMIN);

        return $organization;
    }
}
