<?php

namespace App\Secret\Actions;

use App\Secret\Models\Secret;
use App\Secret\Notifications\SecretNotificationsData;

class UpdateSecretNotifications
{
    public function handle(Secret $secret, SecretNotificationsData $data): Secret
    {
        $secret->update(['notifications' => $data]);

        return $secret;
    }
}
