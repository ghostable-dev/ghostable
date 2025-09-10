<?php

namespace App\Organization\Concerns;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Organization\Contracts\SupportsOverrides as ContractsSupportsOverrides;
use App\Organization\Enums\OrganizationPermission;

trait EvaluatesPermissionOverrides
{
    /**
     * Determine if a user has permission to perform an action
     * on a resource that supports overrides (e.g., Project or Environment).
     *
     * If the resource is an Environment, its overrides take precedence.
     * Falls back to Project permissions or organization role-based defaults.
     */
    public function hasPermission(
        User $user,
        ContractsSupportsOverrides $resource,
        OrganizationPermission|string $permission
    ): bool {
        $permission = is_string($permission)
            ? OrganizationPermission::from($permission)
            : $permission;

        $organization = $resource->owningOrganization();

        // 1: Admins and owners always allowed
        if ($user->isOrganizationAdmin($organization)) {
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

        // 5: Fallback to organization role
        return $user->organizationMembership()->hasOrganizationPermission(
            permission: $permission,
            organization: $organization
        );
    }
}
