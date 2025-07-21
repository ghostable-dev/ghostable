<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Environment\Notifications\EnvironmentNotificationsData;

class UpdateEnvironmentNotifications
{
    public function handle(Environment $environment, EnvironmentNotificationsData $data): Environment
    {
        $environment->update(['notifications' => $data]);

        return $environment;
    }
}
