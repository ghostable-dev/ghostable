<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Events\InviteAccepted;
use App\Organization\Models\Invite;

class AcceptInvite
{
    public function handle(User $user, Invite $invite): void
    {
        $user->organizationMembership()->assignToOrganization(organization: $invite->organization, role: $invite->role);

        $invite->markAsAccepted();

        InviteAccepted::dispatch($invite);
    }
}
