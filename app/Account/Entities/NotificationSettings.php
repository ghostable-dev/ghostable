<?php

namespace App\Account\Entities;

use Spatie\LaravelData\Data;

class NotificationSettings extends Data
{
    public function __construct(
        public bool $blog = true,
        public bool $promotional = true,
    ) {}
}
