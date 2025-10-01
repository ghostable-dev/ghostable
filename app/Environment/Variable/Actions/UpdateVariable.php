<?php

namespace App\Environment\Variable\Actions;

use App\Environment\Variable\Entities\UpdateVariableData;
use App\Environment\Variable\Events\VariableUpdated;
use App\Environment\Variable\Models\EnvironmentVariable;

class UpdateVariable
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
    public function handle(UpdateVariableData $data): EnvironmentVariable
    {
        $data->variable->update([
            'value' => $data->value,
            'is_commented' => is_null($data->is_commented)
                ? $data->variable->is_commented
                : $data->is_commented,
            'is_vapor_secret' => is_null($data->is_vapor_secret)
                ? $data->variable->is_vapor_secret
                : $data->is_vapor_secret,
            'last_updated_at' => now(),
            'last_updated_by' => $data->updatedBy?->id,
        ]);

        $data->variable->createVersionBy($data->updatedBy);

        $data->variable->logActivity('updated', user: $data->updatedBy);

        VariableUpdated::dispatch($data->variable);

        return $data->variable;
    }
}
