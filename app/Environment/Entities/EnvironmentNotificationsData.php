<?php

namespace App\Environment\Entities;

use Spatie\LaravelData\Data;

class EnvironmentNotificationsData extends Data
{
    public function __construct(
        public bool $variable_updated = true,
    ) {}
}
