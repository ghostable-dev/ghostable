<?php

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Models\EnvironmentVariable;

class LogVariableActivity
{
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
            'created' => "Added variable \"{$key}\" to \"{$environment}\"",
            'updated' => "Updated variable \"{$key}\" in \"{$environment}\"",
            'deleted' => "Removed variable \"{$key}\" from \"{$environment}\"",
            'revealed' => "Revealed value for \"{$key}\" in \"{$environment}\"",
            default => ucfirst($event)." variable \"{$key}\" in \"{$environment}\"",
        };
    }
}
