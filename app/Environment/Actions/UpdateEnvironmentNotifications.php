<?php

namespace App\Environment\Actions;

use App\Environment\Entities\EnvironmentNotificationsData;
use App\Environment\Models\Environment;

class UpdateEnvironmentNotifications
{
    public function handle(Environment $environment, EnvironmentNotificationsData $data): Environment
    {
        $environment->update(['notifications' => $data]);

        return $environment;
    }
}
