<?php

namespace App\Environment\Actions;

use App\Environment\Entities\CreateEnvVariableData;
use App\Environment\Models\EnvironmentVariable;

class CreateEnvVariable
{
    /**
     * Create a new environment variable within the given environment.
     *
     * This method:
     * - Creates the variable with its initial key, value, and comment state
     * - Associates it with the environment and user who created it
     * - Records the initial version in the version history table
     * - Logs the creation event for auditing purposes
     */
    public function handle(CreateEnvVariableData $data): EnvironmentVariable
    {
        $var = new EnvironmentVariable([
            'key' => $data->key,
            'value' => $data->value,
            'is_commented' => $data->is_commented,
            'last_updated_at' => now(),
        ]);

        $var->environment()->associate($data->environment);
        $var->lastUpdatedBy()->associate($data->createdBy);
        $var->save();

        $var->createVersionBy($data->createdBy);

        $var->logActivity('created', user: $data->createdBy);

        return $var;
    }
}
