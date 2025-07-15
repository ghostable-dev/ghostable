<?php

namespace App\Environment\Validation\Actions;

use App\Account\Models\User;
use App\Environment\Validation\Models\EnvironmentVariableRule;

class LogVariableRuleActivity
{
    /**
     * Record an activity log entry for a rule-related action.
     *
     * This should be called explicitly from the action layer to track meaningful
     * user-initiated behavior such as creation, editing, or deletion.
     *
     * @param  EnvironmentVariableRule  $rule  The rule being acted on.
     * @param  string  $event  The event type (e.g. 'created', 'updated', 'deleted').
     * @param  User|null  $user  The user responsible for the action (optional).
     */
    public function handle(
        EnvironmentVariableRule $rule,
        string $event,
        ?User $user = null
    ): void {
        activity('env-rule')
            ->performedOn($rule)
            ->causedBy($user)
            ->event($event)
            ->withProperties([
                'key' => $rule->key,
                'type' => $rule->type->value,
                'environment' => [
                    'id' => $rule->environment->id,
                    'name' => $rule->environment->name,
                ],
            ])
            ->log($this->message($event, $rule));
    }

    /**
     * Generate a human-readable log message based on the event type and rule context.
     */
    protected function message(string $event, EnvironmentVariableRule $rule): string
    {
        $key = $rule->key;
        $environment = $rule->environment->name;

        return match ($event) {
            'created' => "Added validation rule for \"{$key}\" in \"{$environment}\"",
            'updated' => "Updated validation rule for \"{$key}\" in \"{$environment}\"",
            'deleted' => "Removed validation rule for \"{$key}\" from \"{$environment}\"",
            default => ucfirst($event)." rule \"{$key}\" in \"{$environment}\"",
        };
    }
}
