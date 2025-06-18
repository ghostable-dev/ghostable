<?php

namespace App\Project\Policies;

use App\Account\Models\User;
use App\Project\Models\Project;
use App\Team\Concerns\EvaluatesPermissionOverrides;
use App\Team\Enums\TeamPermission;
use App\Team\Models\Team;

class ProjectPolicy
{
    use EvaluatesPermissionOverrides;

    /**
     * Determine if the user can view the given project.
     *
     * Viewing is allowed as long as the user is a member of the team
     * that owns the project, regardless of specific permissions.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->teamMembership()->belongsToTeam($project->owningTeam());
    }

    /**
     * Determine if the user can create a new project within the given team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->teamMembership()->hasTeamPermission(
            permission: TeamPermission::CreateProjects,
            team: $team
        );
    }

    /**
     * Determine if the user can delete the given project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->teamMembership()->hasTeamPermission(
            permission: TeamPermission::DeleteProjects,
            team: $project->owningTeam()
        );
    }

    /**
     * General-purpose permission check for a specific action on the project.
     *
     * This method allows dynamic policy checks using a TeamPermission enum
     * and delegates to the shared hasPermission logic.
     */
    public function perform(
        User $user,
        Project $project,
        TeamPermission $permission
    ): bool {
        return $this->hasPermission($user, $project, $permission);
    }
}
