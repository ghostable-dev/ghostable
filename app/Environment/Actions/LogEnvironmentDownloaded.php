<?php

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Models\Environment;

class LogEnvironmentDownloaded
{
    /**
     * Log that a user downloaded the environment file.
     */
    public function handle(Environment $environment, User $user, string $source = 'ui'): void
    {
        activity('variable')
            ->performedOn($environment)
            ->causedBy($user)
            ->event('downloaded')
            ->withProperties([
                'source' => $source,
            ])->log("Downloaded environment file for \"{$environment->name}\" via {$source}");
    }
}
