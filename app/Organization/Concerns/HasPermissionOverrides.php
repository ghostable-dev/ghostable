<?php

namespace App\Organization\Concerns;

use App\Account\Models\User;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\OrganizationPermissionOverride;
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
     * When restricted, organization roles are ignored and only users with
     * permission overrides can access protected functionality.
     */
    public function isRestricted(): bool
    {
        return (bool) $this->is_restricted;
    }

    /**
     * Get all permission overrides associated with this resource.
     *
     * @return MorphMany<OrganizationPermissionOverride>
     */
    public function permissionOverrides(): MorphMany
    {
        return $this->morphMany(OrganizationPermissionOverride::class, 'target');
    }

    /**
     * Check if the given user has a specific permission
     * override on this resource.
     */
    public function userHasOverride(User $user, OrganizationPermission $permission): bool
    {
        return $this->permissionOverrides()
            ->forUser($user)
            ->withPermission($permission)
            ->exists();
    }
}
