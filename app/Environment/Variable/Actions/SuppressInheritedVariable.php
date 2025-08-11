<?php

namespace App\Environment\Variable\Actions;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Variable\Entities\CreateVariableData;

class SuppressInheritedVariable
{
    /**
     * Suppress an inherited environment variable in the given environment.
     *
     * This action creates a "tombstone" record for the specified key, marking it as
     * deleted (`is_deleted = true`) in the target environment. This prevents the
     * inherited value from an ancestor environment from being visible or used in
     * this environment.
     *
     * The suppression is implemented by creating a new EnvironmentVariable entry
     * flagged as deleted, without altering the original variable in its source
     * environment.
     *
     * An optional user may be provided to associate the suppression activity with
     * a specific actor for audit purposes.
     */
    public function handle(string $key, Environment $environment, ?User $suppressedBy = null): void
    {
        $data = new CreateVariableData(
            environment: $environment,
            key: $key,
            value: '',
            is_deleted: true,
            is_override: false,
            createdBy: $suppressedBy,
        );

        $var = resolve(CreateVariable::class)->handle(data: $data, silently: true);

        $var->logActivity(LogVariableActivity::SUPPRESS_INHERITED, $suppressedBy);
    }
}
