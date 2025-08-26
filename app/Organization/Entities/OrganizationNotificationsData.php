<?php

namespace App\Organization\Entities;

use Spatie\LaravelData\Data;

class OrganizationNotificationsData extends Data
{
    public function __construct(
        public bool $membership_activity = true,
        public bool $access_change = true,
        public bool $organization_settings_changed = true,
        public bool $project_activity = true,
    ) {}
}
