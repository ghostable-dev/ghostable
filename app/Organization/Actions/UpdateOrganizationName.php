<?php

namespace App\Organization\Actions;

use App\Organization\Events\OrganizationSettingsChanged;
use App\Organization\Models\Organization;

class UpdateOrganizationName
{
    public function handle(Organization $organization, string $name): Organization
    {
        $organization->update(['name' => $name]);

        OrganizationSettingsChanged::dispatch($organization);

        return $organization;
    }
}
