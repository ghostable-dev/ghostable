<?php

namespace App\Team\Policies;

use App\Account\Models\User;
use App\Team\Enums\TeamPermission;
use App\Team\Models\Team;

class TeamPolicy
{
    public function createProjects(User $user, Team $team): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::ProjectCreate,
            team: $team
        );
    }

    public function manageBilling(User $user, Team $team): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::BillingManage,
            team: $team
        );
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::MemberManage,
            team: $team
        );

    }

    public function admin(User $user, Team $team): bool
    {
        return $user->isTeamAdmin($team);
    }

    public function view(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function delete(User $user, Team $team): bool
    {
        return false;
    }
}
