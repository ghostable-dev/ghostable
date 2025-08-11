<?php

namespace App\Environment\Variable\Actions;

use App\Account\Models\User;
use App\Environment\Variable\Models\EnvironmentVariable;

class LogVariableActivity
{
    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const DELETED = 'deleted';

    public const REVEALED = 'revealed';

    public const REINSTATE_INHERITED = 'reinstated-inherited';

    public const SUPPRESS_INHERITED = 'suppress-inherited';

    public const REINSTATE_OVERRIDE = 'reinstated-override';

    public const SUPPRESS_OVERRIDE = 'suppress-override';

    public const REMOVED_OVERRIDE = 'removed-override';

    public const RESTORED = 'restored';

    /**
     * Record an activity log entry for a variable-related action.
     *
     * This should be called explicitly from the action layer to track meaningful
     * user-initiated behavior such as creation, update, deletion, or reveal.
     */
    public function handle(
        EnvironmentVariable $variable,
        string $event,
        ?User $user = null
    ): void {
        activity('variable')
            ->performedOn($variable)
            ->causedBy($user)
            ->event($event)
            ->withProperties([
                'environment' => $variable->environment->name,
                'key' => $variable->key,
                'is_commented' => $variable->is_commented,
            ])->log($this->message($event, $variable));
    }

    /**
     * Generate a human-readable log message based on the event type and variable context.
     */
    protected function message(string $event, EnvironmentVariable $variable): string
    {
        $key = $variable->key;

        $environment = $variable->environment->name;

        return match ($event) {
            self::CREATED => "Added variable \"{$key}\" to \"{$environment}\"",
            self::UPDATED => "Updated variable \"{$key}\" in \"{$environment}\"",
            self::DELETED => "Removed variable \"{$key}\" from \"{$environment}\"",
            self::REVEALED => "Revealed value for \"{$key}\" in \"{$environment}\"",
            self::RESTORED => "Restored variable \"{$key}\" in \"{$environment}\"",
            self::SUPPRESS_INHERITED => "Suppress inherited variable \"{$key}\" in \"{$environment}\"",
            self::SUPPRESS_OVERRIDE => "Suppress local override for \"{$key}\" in \"{$environment}\"",
            self::REMOVED_OVERRIDE => "Removed overriden variable \"{$key}\" in \"{$environment}\"",
            default => ucfirst($event)." variable \"{$key}\" in \"{$environment}\"",
        };
    }
}
