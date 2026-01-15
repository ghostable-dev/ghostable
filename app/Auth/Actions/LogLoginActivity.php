<?php

namespace App\Auth\Actions;

use App\Account\Models\User;

class LogLoginActivity
{
    public function successful(User $user, array $context = []): void
    {
        $this->record(
            event: 'login',
            user: $user,
            description: "Logged in user \"{$user->email}\"",
            context: $context,
            causer: $user
        );
    }

    public function failed(?User $user, string $email, string $reason, array $context = []): void
    {
        $this->record(
            event: 'login_failed',
            user: $user,
            description: "Failed login for \"{$email}\"",
            context: array_merge($context, [
                'email' => $email,
                'reason' => $reason,
            ])
        );
    }

    protected function record(
        string $event,
        ?User $user,
        string $description,
        array $context = [],
        ?User $causer = null
    ): void {
        $logger = activity('user')->event($event)->withProperties($context);

        if ($user) {
            $logger->performedOn($user);
        }

        if ($causer) {
            $logger->causedBy($causer);
        }

        $logger->log($description);
    }
}
