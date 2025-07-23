<?php

namespace App\Environment\Actions;

use App\Environment\Entities\UpdateEnvVariableData;
use App\Environment\Events\EnvironmentVariableUpdated;
use App\Environment\Models\EnvironmentVariable;

class UpdateEnvVariable
{
    /**
     * Update the value and/or comment status of an existing environment variable.
     *
     * This method:
     * - Updates the variable's value and optional is_commented flag
     * - Tracks the user and time of the change
     * - Stores a new version in the version history
     * - Logs the update event for auditing purposes
     */
    public function handle(UpdateEnvVariableData $data): EnvironmentVariable
    {
        $data->variable->update([
            'value' => $data->value,
            'is_commented' => is_null($data->is_commented)
                ? $data->variable->is_commented
                : $data->is_commented,
            'last_updated_at' => now(),
            'last_updated_by' => $data->updatedBy?->id,
        ]);

        $data->variable->createVersionBy($data->updatedBy);

        $data->variable->logActivity('updated', user: $data->updatedBy);
        
        EnvironmentVariableUpdated::dispatch($data->variable);

        return $data->variable;
    }
}
