<?php

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Models\Environment;

class LogEnvironmentImported
{
    /**
     * Log that a user imported an environment file.
     */
    public function handle(Environment $environment, User $user, string $source = 'ui'): void
    {
        activity('variable')
            ->performedOn($environment)
            ->causedBy($user)
            ->event('imported')
            ->withProperties([
                'source' => $source,
            ])->log("Imported environment file for \"{$environment->name}\" via {$source}");
    }
}
