<?php

namespace App\Environment\Variable\Actions;

use App\Account\Models\User;
use App\Environment\Variable\Models\EnvironmentVariable;
use LogicException;

class ReinstateInheritedVariable
{
    /**
     * Reinstate an inherited environment variable that was previously suppressed
     * in the current environment.
     *
     * This action removes the suppression record (tombstone) for the given variable,
     * allowing the value from its parent or ancestor environment to be visible and
     * active again.
     *
     * An optional $reinstatedBy user may be provided to associate the reinstatement
     * with a specific actor for audit logging purposes.
     */
    public function handle(EnvironmentVariable $var, ?User $reinstatedBy = null): void
    {
        if (! $var->is_deleted) {
            throw new LogicException('Cannot reinstate inheritance: variable is not suppressed.');
        }

        $var->delete();

        $var->logActivity(LogVariableActivity::REINSTATE_INHERITED, user: $reinstatedBy);
    }
}
