<?php

namespace App\Account\Actions\UserStatus;

use App\Account\Enums\UserStatus;
use App\Account\Models\User;

class SuspendUser
{
    /**
     * Suspend the user account.
     *
     * Side effects on the user:
     * - Persists status = SUSPENDED.
     * - Rotates the remember token and revokes all personal access tokens.
     * - Blocks authentication while the status remains suspended.
     * - Records an activity log entry for the "suspended" event.
     */
    public function handle(User $user, ?User $actor = null, ?string $reason = null): void
    {
        app(ChangeUserStatus::class)->handle(
            user: $user,
            status: UserStatus::SUSPENDED,
            event: 'suspended',
            actor: $actor,
            reason: $reason
        );
    }
}
