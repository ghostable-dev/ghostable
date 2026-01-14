<?php

namespace App\Account\Actions\UserStatus;

use App\Account\Enums\UserStatus;
use App\Account\Models\User;

class ReinstateUser
{
    /**
     * Reinstate the user account to active.
     *
     * Side effects on the user:
     * - Persists status = ACTIVE.
     * - Does not rotate remember token or revoke access tokens.
     * - Records an activity log entry for the "reinstated" event.
     */
    public function handle(User $user, ?User $actor = null, ?string $reason = null): void
    {
        app(ChangeUserStatus::class)->handle(
            user: $user,
            status: UserStatus::ACTIVE,
            event: 'reinstated',
            actor: $actor,
            reason: $reason
        );
    }
}
