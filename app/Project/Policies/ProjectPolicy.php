<?php

namespace App\Project\Policies;

use App\Account\Models\User;
use App\Organization\Concerns\EvaluatesPermissionOverrides;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\Organization;
use App\Project\Models\Project;

class ProjectPolicy
{
    use EvaluatesPermissionOverrides;

    /**
     * Determine if the user can view the given project.
     *
     * Viewing is allowed as long as the user is a member of the organization
     * that owns the project, regardless of specific permissions.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->organizationMembership()->belongsToOrganization($project->owningOrganization());
    }

    /**
     * Determine if the user can create a new project within the given organization.
     */
    public function create(User $user, Organization $organization): bool
    {
        return $user->organizationMembership()->hasOrganizationPermission(
            permission: OrganizationPermission::CreateProjects,
            organization: $organization
        );
    }

    /**
     * Determine if the user can delete the given project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->organizationMembership()->hasOrganizationPermission(
            permission: OrganizationPermission::DeleteProjects,
            organization: $project->owningOrganization()
        );
    }

    /**
     * General-purpose permission check for a specific action on the project.
     *
     * This method allows dynamic policy checks using a OrganizationPermission enum
     * and delegates to the shared hasPermission logic.
     */
    public function perform(
        User $user,
        Project $project,
        OrganizationPermission $permission
    ): bool {
        return $this->hasPermission($user, $project, $permission);
    }
}
