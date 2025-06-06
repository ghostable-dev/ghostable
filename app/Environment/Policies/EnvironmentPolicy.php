<?php

namespace App\Environment\Policies;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Team\Concerns\EvaluatesPermissionOverrides;
use App\Team\Enums\TeamPermission;

class EnvironmentPolicy
{
    use EvaluatesPermissionOverrides;
     
    /**
     * Determine if the user can view the environment's metadata.
     *
     * This includes seeing the environment in the UI (name, type),
     * but not necessarily its variables or values.
     */
    public function view(User $user, Environment $environment): bool
    {
        return $user->belongsToTeam($environment->owningTeam());
    }
    
    /**
     * Determine if the user can manage the environment's settings,
     * such as name and type.
     *
     * This action is not overridable and requires the
     * ManageEnvironmentSettings permission on the owning team.
     */
    public function manageSettings(User $user, Environment $environment): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::ManageEnvironmentSettings,
            team: $environment->project->team
        );
    }
    
    /**
     * General-purpose policy method for environment-level permissions
     * that may be overridden per user.
     *
     * Used for actions like viewing, editing, or pushing variables.
     */
    public function perform(
        User $user, 
        Environment $environment, 
        TeamPermission $permission
    ): bool
    {
        return $this->hasPermission($user, $environment, $permission);
    }
}
