<?php

namespace App\Environment\Policies;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Organization\Concerns\EvaluatesPermissionOverrides;
use App\Organization\Enums\OrganizationPermission;

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
        return $environment->owningOrganization()->usesLegacyProjectExperience()
            && $user->organizationMembership()->belongsToOrganization($environment->owningOrganization());
    }

    /**
     * Determine if the user can manage the environment's settings,
     * such as name and type.
     *
     * This action is not overridable and requires the
     * ManageEnvironmentSettings permission on the owning organization.
     */
    public function manageSettings(User $user, Environment $environment): bool
    {
        return $environment->owningOrganization()->usesLegacyProjectExperience()
            && $user->organizationMembership()->hasOrganizationPermission(
                permission: OrganizationPermission::ManageEnvironmentSettings,
                organization: $environment->project->organization
            );
    }

    /**
     * Determine whether the given user may manage (create, rotate, revoke)
     * CLI tokens for the specified environment.
     */
    public function manageTokens(User $user, Environment $environment): bool
    {
        return $environment->owningOrganization()->usesLegacyProjectExperience()
            && $user->isOrganizationAdmin($environment->owningOrganization());
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
        OrganizationPermission $permission
    ): bool {
        if (! $environment->owningOrganization()->usesLegacyProjectExperience()) {
            return false;
        }

        return $this->hasPermission($user, $environment, $permission);
    }
}
