<?php

namespace App\Team\Entities;

use Spatie\LaravelData\Data;

class TeamNotificationsData extends Data
{
    public function __construct(
        public bool $membership_activity = true,
        public bool $access_change = true,
        public bool $team_settings_changed = true,
        public bool $project_activity = true,
    ) {}
}
