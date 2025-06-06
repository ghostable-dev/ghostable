<?php

namespace App\Team\Concerns;

use App\Account\Models\User;
use App\Team\Enums\TeamPermission;
use App\Team\Models\TeamPermissionOverride;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Provides support for managing and evaluating permission overrides
 * on a resource such as a Project or Environment.
 */
trait HasPermissionOverrides
{
    /**
     * Determine whether this resource is restricted to explicit overrides.
     *
     * When restricted, team roles are ignored and only users with
     * permission overrides can access protected functionality.
     */
    public function isRestricted(): bool
    {
        return (bool) $this->is_restricted;
    }

    /**
     * Get all permission overrides associated with this resource.
     *
     * @return MorphMany<TeamPermissionOverride>
     */
    public function permissionOverrides(): MorphMany
    {
        return $this->morphMany(TeamPermissionOverride::class, 'target');
    }

    /**
     * Check if the given user has a specific permission
     * override on this resource.
     */
    public function userHasOverride(User $user, TeamPermission $permission): bool
    {
        return $this->permissionOverrides()
            ->forUser($user)
            ->withPermission($permission)
            ->exists();
    }
}
