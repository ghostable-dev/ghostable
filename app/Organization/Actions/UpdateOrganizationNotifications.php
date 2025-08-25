<?php

namespace App\Organization\Actions;

use App\Organization\Entities\OrganizationNotificationsData;
use App\Organization\Models\Organization;

class UpdateOrganizationNotifications
{
    public function handle(Organization $organization, OrganizationNotificationsData $data): Organization
    {
        $organization->update(['notifications' => $data]);

        return $organization;
    }
}
