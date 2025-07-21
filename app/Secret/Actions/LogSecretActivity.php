<?php

namespace App\Secret\Actions;

use App\Account\Models\User;
use App\Secret\Models\Secret;

class LogSecretActivity
{
    public function handle(Secret $secret, string $event, ?User $user = null): void
    {
        $owner = $secret->owner;

        $ownerName = method_exists($owner, 'name') ? $owner->name : class_basename($owner);

        activity('secret')
            ->performedOn($secret)
            ->causedBy($user)
            ->event($event)
            ->withProperties([
                'owner_type' => $owner?->getMorphClass(),
                'owner_id' => $owner?->id,
                'name' => $secret->name,
            ])
            ->log($this->message($event, $secret, $ownerName));
    }

    protected function message(string $event, Secret $secret, string $ownerName): string
    {
        $name = $secret->name;

        return match ($event) {
            'created' => "Added secret \"{$name}\" to \"{$ownerName}\"",
            'updated' => "Updated secret \"{$name}\" in \"{$ownerName}\"",
            'deleted' => "Removed secret \"{$name}\" from \"{$ownerName}\"",
            default => ucfirst($event)." secret \"{$name}\" in \"{$ownerName}\"",
        };
    }
}
