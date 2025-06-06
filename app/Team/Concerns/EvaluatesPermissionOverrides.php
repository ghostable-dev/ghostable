<?php

namespace App\Team\Concerns;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Team\Contracts\SupportsOverrides as ContractsSupportsOverrides;
use App\Team\Enums\TeamPermission;

trait EvaluatesPermissionOverrides
{
    /**
     * Determine if a user has permission to perform an action
     * on a resource that supports overrides (e.g., Project or Environment).
     *
     * If the resource is an Environment, its overrides take precedence.
     * Falls back to Project permissions or team role-based defaults.
     */
    public function hasPermission(
        User $user, 
        ContractsSupportsOverrides $resource, 
        TeamPermission|string $permission
    ): bool
    {
        $permission = is_string($permission)
            ? TeamPermission::from($permission)
            : $permission;

        $team = $resource->owningTeam();

        // 1: Admins and owners always allowed
        if ($user->isTeamAdmin($team)) {
            return true;
        }
        
        // 2: If resource is an Environment, check override first
        if (
            $resource instanceof Environment &&
            $resource->isRestricted() &&
            $resource->userHasOverride(
                user: $user, 
                permission: $permission
            )
        ) {
            return true;
        }

        // 3: If Environment fallback to its project
        if ($resource instanceof Environment) {
            return $this->hasPermission(
                user: $user,
                resource: $resource->project,
                permission: $permission
            );
        }

        // 4: For other resources (like Project), check override if restricted
        if ($resource->isRestricted()) {
            return $resource->userHasOverride(
                user: $user, 
                permission: $permission
            );
        }

        // 5: Fallback to team role
        return $user->hasTeamPermission(
            permission: $permission,
            team: $team
        );
    }
}