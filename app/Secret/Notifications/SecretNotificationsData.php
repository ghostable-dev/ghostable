<?php

namespace App\Secret\Notifications;

use Spatie\LaravelData\Data;

class SecretNotificationsData extends Data
{
    public function __construct(
        public bool $secret_updated = true,
    ) {}
}
