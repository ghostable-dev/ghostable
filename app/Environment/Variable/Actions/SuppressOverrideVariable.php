<?php

namespace App\Environment\Variable\Actions;

use App\Account\Models\User;
use App\Environment\Variable\Models\EnvironmentVariable;

class SuppressOverrideVariable
{
    /**
     * Suppress (tombstone) an overridden environment variable in the current environment.
     *
     * This action marks the override as deleted (`is_deleted = true`), preventing
     * its value from being used. Once suppressed, the environment will fall back
     * to the inherited value from its parent environment (if any).
     *
     * An optional $suppressedBy user may be provided to associate the suppression
     * with a specific actor for audit history.
     */
    public function handle(EnvironmentVariable $var, ?User $suppressedBy = null): void
    {
        $var->update([
            'is_deleted' => true,
            'last_updated_at' => now(),
            'last_updated_by' => $suppressedBy?->id,
        ]);

        $var->createVersionBy($suppressedBy);

        $var->logActivity(LogVariableActivity::SUPPRESS_OVERRIDE, user: $suppressedBy);
    }
}
