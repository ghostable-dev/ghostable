<?php

namespace App\Organization\Actions;

use App\Organization\Events\OrganizationSettingsChanged;
use App\Organization\Models\Organization;

class UpdateOrganizationSettings
{
    public function handle(Organization $organization, array $payload): Organization
    {
        $organization->fill($payload);

        if (! $organization->isDirty()) {
            return $organization;
        }

        $organization->save();

        OrganizationSettingsChanged::dispatch($organization);

        return $organization;
    }
}
