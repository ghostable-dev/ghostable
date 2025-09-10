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
        return $user->organizationMembership()->belongsToOrganization($environment->owningOrganization());
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
        return $user->organizationMembership()->hasOrganizationPermission(
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
        return $user->isOrganizationAdmin($environment->owningOrganization());
    }

    /**
     * Determine if the user can update the base (parent) environment
     * that the given environment inherits variables from.
     */
    public function updateBase(User $user, Environment $environment, ?Environment $base): bool
    {
        // Switching to standalone
        if (is_null($base)) {
            return $this->manageSettings($user, $environment);
        }

        // Only allow within the same project
        if ($environment->project_id !== $base->project_id) {
            return false;
        }

        // Not self, block cycles
        if ($environment->is($base) || $base->isDescendantOf($environment)) {
            return false;
        }

        // Require organization-level ManageEnvironmentSettings (not overridable)
        if (! $this->manageSettings($user, $environment)
            || ! $this->perform($user, $environment, OrganizationPermission::EditVariables)) {
            return false;
        }

        return true;
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
        return $this->hasPermission($user, $environment, $permission);
    }
}
