<?php

namespace App\Environment\Actions\Token;

use App\Account\Models\User;
use App\Auth\Models\PersonalAccessToken;

class LogEnvTokenActivity
{
    /**
     * Record an activity log entry for a env token-related action.
     *
     * This should be called explicitly from the action layer to track meaningful
     * user-initiated behavior such as creation or deletion.
     */
    public function handle(
        PersonalAccessToken $token,
        string $event,
        ?User $user = null
    ): void {
        activity('cli-token')
            ->performedOn($token)
            ->causedBy($user)
            ->event($event)
            ->withProperties([
                'name' => $token->name,
                'environment' => [
                    'id' => $token->tokenable->name,
                    'name' => $token->tokenable->name,
                ],
            ])->log($this->message($event, $token));
    }

    /**
     * Generate a human-readable log message based on the event type and token context.
     */
    protected function message(string $event, PersonalAccessToken $token): string
    {
        $name = $token->name;

        $environment = $token->tokenable->name;

        return match ($event) {
            'created' => "Added CLI token \"{$name}\" for \"{$environment}\"",
            'deleted' => "Removed CLI token \"{$name}\" from \"{$environment}\"",
            default => ucfirst($event)." token \"{$name}\" for \"{$environment}\"",
        };
    }
}
