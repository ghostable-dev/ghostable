<?php

namespace App\Organization\Policies;

use App\Account\Models\User;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\Organization;

class OrganizationPolicy
{
    public function createProjects(User $user, Organization $organization): bool
    {
        return $user->organizationMembership()->hasOrganizationPermission(
            permission: OrganizationPermission::CreateProjects,
            organization: $organization
        );
    }

    /**
     * Determine if the user can manage members of the given organization.
     *
     * This includes inviting, removing, and updating roles for other organization members.
     */
    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->organizationMembership()->hasOrganizationPermission(
            permission: OrganizationPermission::ManageOrganizationMembers,
            organization: $organization
        );
    }

    /**
     * Determine if the user can manage settings of the given organization.
     *
     * This includes updating the organization name and icon.
     */
    public function manageSettings(User $user, Organization $organization): bool
    {
        return $user->organizationMembership()->hasOrganizationPermission(
            permission: OrganizationPermission::ManageOrganizationSettings,
            organization: $organization
        );
    }

    /**
     * Determine if the user can manage access controls for the given organization.
     *
     * This includes creating, updating, and removing permission overrides
     * for projects and environments within the organization.
     */
    public function manageAccessControls(User $user, Organization $organization): bool
    {
        // Advanced access controls (overrides) are a paid feature.
        if (! $organization->features->advanced_permissions) {
            return false;
        }

        return $user->organizationMembership()->hasOrganizationPermission(
            permission: OrganizationPermission::ManageAccessControls,
            organization: $organization
        );
    }

    /**
     * Determine if the user can manage billing of the given organization.
     */
    public function manageBilling(User $user, Organization $organization): bool
    {
        return $user->organizationMembership()->hasOrganizationPermission(
            permission: OrganizationPermission::ManageBilling,
            organization: $organization
        );
    }

    /**
     * Determine if the user can view audit logs of the given organization.
     */
    public function viewAuditLogs(User $user, Organization $organization): bool
    {
        return $organization->features->audits &&
            $user->organizationMembership()->hasOrganizationPermission(
                permission: OrganizationPermission::ViewAuditLogs,
                organization: $organization
            );
    }

    public function manageAuditWebhooks(User $user, Organization $organization): bool
    {
        return $organization->features->audit_webhooks
            && $user->isOrganizationAdmin($organization);
    }

    public function admin(User $user, Organization $organization): bool
    {
        return $user->isOrganizationAdmin($organization);
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->organizationMembership()->belongsToOrganization($organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return false;
    }
}
