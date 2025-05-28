<?php

namespace App\Team\Policies;

use App\Account\Enums\Permission;
use App\Account\Models\User;
use App\Team\Models\Team;

class TeamPolicy
{
    public function createProjects(User $user, Team $team): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::ProjectCreate,
            team: $team
        );
    }
    
    public function manageBilling(User $user, Team $team): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::BillingManage,
            team: $team
        );
    }

    public function removeMembers(User $user, Team $team): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::MemberRemove,
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
