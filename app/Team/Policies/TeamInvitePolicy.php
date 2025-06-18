<?php

namespace App\Team\Policies;

use App\Account\Models\User;
use App\Team\Enums\TeamPermission;
use App\Team\Models\Team;
use App\Team\Models\TeamInvite;

class TeamInvitePolicy
{
    /**
     * Determine if the user can create invites for the given team.
     */
    public function create(User $user, Team $team): bool
    {
        return $this->manage(user: $user, team: $team);
    }

    /**
     * Determine if the user can delete this invite.
     */
    public function delete(User $user, TeamInvite $invite): bool
    {
        return $this->manage(user: $user, team: $invite->team);
    }

    /**
     * Determine if the user can "resend" this invite.
     */
    public function resend(User $user, TeamInvite $invite): bool
    {
        return $this->manage(user: $user, team: $invite->team);
    }

    /**
     * Determine if the user can accept the invite sent to them.
     */
    public function accept(User $user, TeamInvite $invite): bool
    {
        return $user->isVerified() && $user->email === $invite->email;
    }

    /**
     * Determine if the user can decline the invite sent to them.
     */
    public function decline(User $user, TeamInvite $invite): bool
    {
        return $user->isVerified() && $user->email === $invite->email;
    }

    /**
     * Shared authorization logic for managing team invites.
     *
     * Used by create and delete checks to validate team permissions.
     */
    private function manage(User $user, Team $team): bool
    {
        return $user->teamMembership()->hasTeamPermission(
            permission: TeamPermission::ManageTeamMembers,
            team: $team
        );
    }
}
