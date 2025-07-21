<?php

namespace App\Environment\Notifications;

use Spatie\LaravelData\Data;

class EnvironmentNotificationsData extends Data
{
    public function __construct(
        public bool $variable_updated = true,
    ) {}
}
