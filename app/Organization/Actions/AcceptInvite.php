<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Events\InviteAccepted;
use App\Organization\Models\OrganizationInvite;

class AcceptInvite
{
    public function handle(User $user, OrganizationInvite $invite): void
    {
        $user->organizationMembership()->assignToOrganization(organization: $invite->organization, role: $invite->role);

        $invite->markAsAccepted();

        InviteAccepted::dispatch($invite);
    }
}
