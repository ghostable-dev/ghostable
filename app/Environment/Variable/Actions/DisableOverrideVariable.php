<?php

namespace App\Environment\Variable\Actions;

use App\Account\Models\User;
use App\Environment\Variable\Models\EnvironmentVariable;

class DisableOverrideVariable
{
    /**
     * Disable a locally defined override by marking it as deleted.
     *
     * This prevents the environment from falling back to the inherited value
     * without removing the variable entirely. The method:
     *
     * - Marks the variable as deleted
     * - Records who disabled it
     * - Creates a version snapshot of its final state
     * - Logs the action for audit purposes
     *
     * This differs from a full delete, as the variable still exists
     * to block inheritance but is no longer active.
     */
    public function handle(
        EnvironmentVariable $var,
        ?User $disabledBy = null
    ): void {
        $var->update([
            'is_deleted' => true,
            'last_updated_at' => now(),
            'last_updated_by' => $disabledBy?->id,
        ]);

        $var->createVersionBy($disabledBy);

        $var->logActivity('disabled-override', user: $disabledBy);
    }
}
