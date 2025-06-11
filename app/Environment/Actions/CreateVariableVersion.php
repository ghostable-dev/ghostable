<?php

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Models\EnvironmentVariable;
use App\Environment\Models\EnvironmentVariableVersion;

class CreateVariableVersion
{
    /**
     * Create a new immutable version record for the given environment variable.
     *
     * This is typically called after a variable
     * is created, updated, deleted, or commented/uncommented.
     * The version stores a snapshot of the key, value, and is_commented state,
     * and is associated with the user who triggered the change.
     */
    public function handle(
        EnvironmentVariable $variable,
        ?User $changedBy = null
    ): EnvironmentVariableVersion {
        $version = new EnvironmentVariableVersion([
            'key' => $variable->key,
            'value' => $variable->value,
            'is_commented' => $variable->is_commented,
            'version' => $variable->versions()->max('version') + 1,
        ]);

        $version->changedBy()->associate($changedBy);
        $version->environmentVariable()->associate($variable);
        $version->save();

        return $version;
    }
}
