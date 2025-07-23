<?php

namespace App\Project\Entities;

use Spatie\LaravelData\Data;

class ProjectNotificationsData extends Data
{
    public function __construct(
        public bool $project_settings_changed = true,
        public bool $environment_activity = true,
    ) {}
}
