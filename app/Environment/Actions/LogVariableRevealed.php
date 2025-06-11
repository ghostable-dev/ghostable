<?php

namespace App\Environment\Actions;

use App\Environment\Models\EnvironmentVariable;
use App\Account\Models\User;

class LogVariableRevealed
{
    /**
     * Log that a sensitive environment variable was revealed to a user.
     *
     * This is intended for auditing purposes when a masked value is shown in the UI,
     * or accessed through another reveal mechanism (e.g., API or CLI).
     */
    public function handle(
        EnvironmentVariable $variable, 
        ?User $user = null
    ): void
    {
        $environment = $variable->environment->name;
        
        activity('variable')
            ->performedOn($variable)
            ->causedBy($user)
            ->event('revealed')
            ->withProperties([
                'key' => $variable->key,
                'environment' => $environment,
            ])->log("Revealed value for \"{$variable->key}\" in \"{$environment}\"");
    }
}