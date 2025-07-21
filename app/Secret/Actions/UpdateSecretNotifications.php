<?php

namespace App\Secret\Actions;

use App\Secret\Entities\SecretNotificationsData;
use App\Secret\Models\Secret;

class UpdateSecretNotifications
{
    public function handle(Secret $secret, SecretNotificationsData $data): Secret
    {
        $secret->update(['notifications' => $data]);

        return $secret;
    }
}
