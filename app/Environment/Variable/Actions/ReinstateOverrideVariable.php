<?php

namespace App\Environment\Variable\Actions;

use App\Account\Models\User;
use App\Environment\Variable\Models\EnvironmentVariable;
use LogicException;

class ReinstateOverrideVariable
{
    /**
     * Reinstate an overridden environment variable that was previously suppressed
     * (tombstone) in the current environment.
     *
     * This action reactivates the override so its value takes precedence over any
     * inherited value from a parent environment.
     *
     * An optional $reinstatedBy user may be provided to associate the reinstatement
     * activity with a specific actor for auditing purposes.
     */
    public function handle(EnvironmentVariable $var, ?User $reinstatedBy = null): void
    {
        if (! $var->is_deleted) {
            throw new LogicException('Cannot reinstate unsuppressed variable.');
        }

        $var->is_deleted = false;

        $var->save();

        $var->logActivity(LogVariableActivity::REINSTATE_OVERRIDE, user: $reinstatedBy);
    }
}
