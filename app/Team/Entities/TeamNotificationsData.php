<?php

namespace App\Team\Entities;

use Spatie\LaravelData\Data;

class TeamNotificationsData extends Data
{
    public function __construct(
        public bool $project_created = true,
        public bool $project_deleted = true,
    ) {}
}
