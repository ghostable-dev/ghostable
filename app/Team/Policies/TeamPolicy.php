<?php

namespace App\Team\Policies;

use App\Account\Models\User;
use App\Team\Enums\TeamPermission;
use App\Team\Models\Team;

class TeamPolicy
{
    public function createProjects(User $user, Team $team): bool
    {
        return $user->teamMembership()->hasTeamPermission(
            permission: TeamPermission::CreateProjects,
            team: $team
        );
    }

    /**
     * Determine if the user can manage members of the given team.
     *
     * This includes inviting, removing, and updating roles for other team members.
     */
    public function manageMembers(User $user, Team $team): bool
    {
        return $user->teamMembership()->hasTeamPermission(
            permission: TeamPermission::ManageTeamMembers,
            team: $team
        );
    }

    /**
     * Determine if the user can manage settings of the given team.
     *
     * This includes updating the team name and icon.
     */
    public function manageSettings(User $user, Team $team): bool
    {
        return $user->teamMembership()->hasTeamPermission(
            permission: TeamPermission::ManageTeamSettings,
            team: $team
        );
    }

    /**
     * Determine if the user can manage access controls for the given team.
     *
     * This includes creating, updating, and removing permission overrides
     * for projects and environments within the team.
     */
    public function manageAccessControls(User $user, Team $team): bool
    {
        return $user->teamMembership()->hasTeamPermission(
            permission: TeamPermission::ManageAccessControls,
            team: $team
        );
    }

    /**
     * Determine if the user can manage billing of the given team.
     */
    public function manageBilling(User $user, Team $team): bool
    {
        return $user->teamMembership()->hasTeamPermission(
            permission: TeamPermission::ManageBilling,
            team: $team
        );
    }

    /**
     * Determine if the user can view audit logs of the given team.
     */
    public function viewAuditLogs(User $user, Team $team): bool
    {
        return $user->teamMembership()->hasTeamPermission(
            permission: TeamPermission::ViewAuditLogs,
            team: $team
        );
    }

    public function admin(User $user, Team $team): bool
    {
        return $user->isTeamAdmin($team);
    }

    public function view(User $user, Team $team): bool
    {
        return $user->teamMembership()->belongsToTeam($team);
    }

    public function delete(User $user, Team $team): bool
    {
        return false;
    }
}
