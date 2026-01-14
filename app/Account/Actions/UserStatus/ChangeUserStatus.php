<?php

namespace App\Account\Actions\UserStatus;

use App\Account\Enums\UserStatus;
use App\Account\Models\User;
use Illuminate\Support\Str;

class ChangeUserStatus
{
    /**
     * Update a user's status and record an audit trail.
     *
     * Side effects:
     * - Persists the new status on the user record.
     * - When moving to a non-active status, rotates the remember token and
     *   revokes all personal access tokens.
     * - Writes an activity log entry with the previous/new status and reason.
     */
    public function handle(
        User $user,
        UserStatus $status,
        string $event,
        ?User $actor = null,
        ?string $reason = null
    ): void {
        $previousStatus = $user->status;

        if ($previousStatus === $status) {
            return;
        }

        $attributes = ['status' => $status];

        if ($status !== UserStatus::ACTIVE) {
            $attributes['remember_token'] = Str::random(60);
        }

        $user->forceFill($attributes)->save();

        if ($status !== UserStatus::ACTIVE) {
            $user->tokens()->delete();
        }

        activity('user')
            ->performedOn($user)
            ->causedBy($actor)
            ->event($event)
            ->withProperties([
                'status' => [
                    'from' => $previousStatus->value,
                    'to' => $status->value,
                ],
                'reason' => $reason,
            ])
            ->log($this->statusChangeDescription($event, $user->email));
    }

    protected function statusChangeDescription(string $event, string $email): string
    {
        return match ($event) {
            'suspended' => "Suspended user \"{$email}\"",
            'reinstated' => "Reinstated user \"{$email}\"",
            'locked' => "Locked user \"{$email}\"",
            'unlocked' => "Unlocked user \"{$email}\"",
            default => ucfirst($event)." user \"{$email}\"",
        };
    }
}
