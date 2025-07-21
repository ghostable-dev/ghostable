<?php

namespace App\Secret\Entities;

use Spatie\LaravelData\Data;

class SecretNotificationsData extends Data
{
    public function __construct(
        public bool $secret_updated = true,
    ) {}
}
