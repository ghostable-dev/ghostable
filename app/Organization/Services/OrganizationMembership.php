<?php

namespace App\Organization\Services;

use App\Account\Models\User;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Cache;
use LogicException;

class OrganizationMembership
{
    /**
     * Create a new instance scoped to a specific user.
     */
    public function __construct(protected User $user) {}

    /**
     * Determine if the user belongs to the given organization.
     */
    public function belongsToOrganization(Organization $organization): bool
    {
        return Cache::memo()->rememberForever(
            $this->cacheKeyForMembership($organization, $this->user),
            fn () => $organization->users()->where('user_id', $this->user->id)->exists()
        );
    }

    /**
     * Determine if the user has the given role on the given organization.
     */
    public function hasOrganizationRole(Organization $organization, OrganizationRole $role): bool
    {
        return $this->getMembershipForOrganization($organization)?->pivot->role === $role;
    }

    /**
     * Determine if the user has the given permission on the given organization.
     */
    public function hasOrganizationPermission(Organization $organization, OrganizationPermission $permission): bool
    {
        return Cache::memo()->rememberForever(
            $this->cacheKeyForOrganizationPermission($organization, $permission),
            fn () => $this->getMembershipForOrganization($organization)?->pivot->role?->hasPermission($permission) ?? false
        );
    }

    /**
     * Retrieve the user's membership pivot model for the given organization.
     */
    public function getMembershipForOrganization(Organization $organization): ?Organization
    {
        return Cache::memo()->rememberForever(
            $this->cacheKeyForMembershipRecord($organization, $this->user),
            fn () => $this->user->organizations()->where('organization_id', $organization->id)->first()
        );
    }

    /**
     * Assign the user to the given organization with the specified role.
     *
     * @throws LogicException if the user is already a member of the organization.
     */
    public function assignToOrganization(Organization $organization, OrganizationRole|string $role): void
    {
        if ($this->user->organizations->contains($organization)) {
            throw new LogicException('User is already a member of this organization.');
        }

        $role = is_string($role) ? OrganizationRole::from($role) : $role;

        $this->user->organizations()->attach($organization, [
            'role' => $role->value,
            'permissions' => null,
        ]);

        $this->clearMembershipCache($organization);
    }

    /**
     * Remove the user from the given organization and clear related cache.
     */
    public function removeFromOrganization(Organization $organization): void
    {
        if ($this->user->organizations->contains($organization->id)) {
            $this->user->organizations()->detach($organization->id);
            $this->clearMembershipCache($organization);
        }
    }

    /**
     * Clear all cached membership data for the given organization.
     */
    public function clearMembershipCache(Organization $organization): void
    {
        Cache::forget($this->cacheKeyForMembership($organization));

        Cache::forget($this->cacheKeyForMembershipRecord($organization));
    }

    /**
     * Generate the cache key for checking membership existence.
     */
    protected function cacheKeyForMembership(Organization $organization): string
    {
        return "organization:{$organization->id}:user:{$this->user->id}:belongs";
    }

    /**
     * Generate the cache key for storing the full membership record.
     */
    protected function cacheKeyForMembershipRecord(Organization $organization): string
    {
        return "organizationMembership:{$organization->id}:user:{$this->user->id}";
    }

    protected function cacheKeyForOrganizationPermission(Organization $organization, OrganizationPermission $permission): string
    {
        return "organizationPermission:{$organization->id}:user:{$this->user->id}:{$permission->value}";
    }
}
