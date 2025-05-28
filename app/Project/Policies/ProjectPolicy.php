<?php

namespace App\Project\Policies;

use App\Account\Enums\Permission;
use App\Account\Models\User;
use App\Project\Models\Project;
use App\Team\Models\Team;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->belongsToTeam($project->team);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::ProjectDelete,
            team: $project->team
        );
    }

    public function manage(User $user, Project $project): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::ProjectManage,
            team: $project->team
        );
    }
    
    public function createEnvironments(User $user, Project $project): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::EnvCreate,
            team: $project->team
        );
    }
}