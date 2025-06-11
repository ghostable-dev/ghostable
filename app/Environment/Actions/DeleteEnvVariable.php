<?php

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Models\EnvironmentVariable;

class DeleteEnvVariable
{
    /**
     * Permanently delete an environment variable while preserving its history.
     *
     * This method performs a clean, auditable deletion by:
     * - Recording the user who deleted it
     * - Creating a snapshot version of the variable's final state
     * - Soft-deleting the variable from the database
     * - Logging the action for audit purposes
     */
    public function handle(
        EnvironmentVariable $var,
        ?User $deletedBy = null
    ): void {
        $var->update([
            'last_updated_at' => now(),
            'last_updated_by' => $deletedBy?->id,
        ]);

        $var->createVersionBy($deletedBy);

        $var->delete();

        $var->logActivity('delete', user: $deletedBy);
    }
}
