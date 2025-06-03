<?php

namespace App\Project\Policies;

use App\Account\Models\User;
use App\Project\Models\Project;
use App\Team\Enums\TeamPermission;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->belongsToTeam($project->team);
    }
    
    public function delete(User $user, Project $project): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::ProjectDelete,
            team: $project->team
        );
    }

    public function manage(User $user, Project $project): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::ProjectManage,
            team: $project->team
        );
    }

    public function createEnvironments(User $user, Project $project): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::EnvCreate,
            team: $project->team
        );
    }
}
