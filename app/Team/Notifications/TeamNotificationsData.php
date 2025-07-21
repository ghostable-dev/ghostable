<?php

namespace App\Team\Notifications;

use Spatie\LaravelData\Data;

class TeamNotificationsData extends Data
{
    public function __construct(
        public bool $project_created = true,
        public bool $project_deleted = true,
    ) {}
}
