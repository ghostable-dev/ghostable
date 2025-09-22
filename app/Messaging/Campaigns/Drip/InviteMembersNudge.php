<?php

namespace App\Messaging\Campaigns\Drip;

use App\Account\Models\MailingListEmail;
use App\Account\Models\User;
use App\Core\Enums\NotificationCategory;
use App\Messaging\Mail\Drip\InviteMembersNudgeMailable;
use App\Organization\Enums\InviteStatus;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Builder;

class InviteMembersNudge extends DripCampaign
{
    public function key(): string
    {
        return 'drip.invite-members.v1';
    }

    public function audience(Builder $query): Builder
    {
        return $query->whereHas('organizations');
    }

    public function eligible(User|MailingListEmail $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if (! $user->organizations()->exists()) {
            return false;
        }

        $hasCollaborators = $user->organizations()
            ->whereHas('users', fn (Builder $users) => $users->whereKeyNot($user->getKey()))
            ->exists();

        if ($hasCollaborators) {
            return false;
        }

        $hasInvites = $user->organizations()
            ->whereHas('invites', fn (Builder $invites) => $invites->whereIn('status', [
                InviteStatus::PENDING->value,
                InviteStatus::ACCEPTED->value,
            ]))
            ->exists();

        return ! $hasInvites;
    }

    public function mailable(User|MailingListEmail $user): Mailable
    {
        return new InviteMembersNudgeMailable($user);
    }

    public function categories(): array
    {
        return [NotificationCategory::PRODUCT_TIPS];
    }
}
