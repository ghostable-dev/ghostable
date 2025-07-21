<?php

namespace App\Project\Notifications;

use Spatie\LaravelData\Data;

class ProjectNotificationsData extends Data
{
    public function __construct(
        public bool $environment_created = true,
        public bool $environment_deleted = true,
    ) {}
}
