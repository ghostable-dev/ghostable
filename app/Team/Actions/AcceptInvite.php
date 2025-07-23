<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Events\InviteAccepted;
use App\Team\Models\TeamInvite;

class AcceptInvite
{
    public function handle(User $user, TeamInvite $invite): void
    {
        $user->teamMembership()->assignToTeam(team: $invite->team, role: $invite->role);

        $invite->markAsAccepted();
        
        InviteAccepted::dispatch($invite);
    }
}
