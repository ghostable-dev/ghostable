<?php

namespace App\Environment\Variable\Actions;

use App\Environment\Variable\Entities\CreateVariableData;
use App\Environment\Variable\Models\EnvironmentVariable;

class CreateVariable
{
    /**
     * Create a new environment variable within the given environment.
     *
     * This includes:
     * - Creating the variable with initial state
     * - Linking it to the environment and user
     * - Versioning
     * - Logging the activity (optional)
     */
    public function handle(CreateVariableData $data, bool $silently = false): EnvironmentVariable
    {
        $var = new EnvironmentVariable([
            'key' => $data->key,
            'value' => $data->value,
            'is_commented' => $data->is_commented,
            'is_override' => $data->is_override,
            'is_deleted' => $data->is_deleted,
            'last_updated_at' => now(),
        ]);

        $var->environment()->associate($data->environment);
        $var->lastUpdatedBy()->associate($data->createdBy);
        $var->save();

        $var->createVersionBy($data->createdBy);

        if (! $silently) {
            $var->logActivity('created', user: $data->createdBy);
        }

        return $var;
    }
}
