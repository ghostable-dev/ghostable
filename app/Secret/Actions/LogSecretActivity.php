<?php

namespace App\Secret\Actions;

use App\Account\Models\User;
use App\Secret\Models\Secret;

class LogSecretActivity
{
    public function handle(Secret $secret, string $event, ?User $user = null): void
    {
        $environment = $secret->environment;

        $environmentName = $environment->name;

        activity('secret')
            ->performedOn($secret)
            ->causedBy($user)
            ->event($event)
            ->withProperties([
                'environment_id' => $environment?->id,
                'name' => $secret->name,
            ])
            ->log($this->message($event, $secret, $environmentName));
    }

    protected function message(string $event, Secret $secret, string $environmentName): string
    {
        $name = $secret->name;

        return match ($event) {
            'created' => "Added secret \"{$name}\" to \"{$environmentName}\"",
            'updated' => "Updated secret \"{$name}\" in \"{$environmentName}\"",
            'deleted' => "Removed secret \"{$name}\" from \"{$environmentName}\"",
            default => ucfirst($event)." secret \"{$name}\" in \"{$environmentName}\"",
        };
    }
}
