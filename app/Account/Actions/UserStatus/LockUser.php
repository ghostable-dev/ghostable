<?php

namespace App\Account\Actions\UserStatus;

use App\Account\Enums\UserStatus;
use App\Account\Models\User;

class LockUser
{
    /**
     * Lock the user account.
     *
     * Side effects on the user:
     * - Persists status = LOCKED.
     * - Rotates the remember token and revokes all personal access tokens.
     * - Blocks authentication while the status remains locked.
     * - Records an activity log entry for the "locked" event.
     */
    public function handle(User $user, ?User $actor = null, ?string $reason = null): void
    {
        app(ChangeUserStatus::class)->handle(
            user: $user,
            status: UserStatus::LOCKED,
            event: 'locked',
            actor: $actor,
            reason: $reason
        );
    }
}
