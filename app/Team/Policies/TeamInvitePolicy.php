<?php

namespace App\Team\Policies;

use App\Account\Enums\Permission;
use App\Account\Models\User;
use App\Team\Models\TeamInvite;

class TeamInvitePolicy
{
    public function create(User $user, TeamInvite $invite): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::MemberInvite, 
            team: $invite->team
        );
    }
    
    public function update(User $user, TeamInvite $invite): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::MemberInvite, 
            team: $invite->team
        ) || $user->email === $invite->email;
    }
    
    public function delete(User $user, TeamInvite $invite): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::MemberInvite, 
            team: $invite->team
        ) || $user->email === $invite->email;
    }
}
