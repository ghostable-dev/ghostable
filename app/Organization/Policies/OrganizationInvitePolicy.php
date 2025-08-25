<?php

namespace App\Organization\Policies;

use App\Account\Models\User;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationInvite;

class OrganizationInvitePolicy
{
    /**
     * Determine if the user can create invites for the given organization.
     */
    public function create(User $user, Organization $organization): bool
    {
        if ($organization->is_personal) {
            return false;
        }

        return $this->manage(user: $user, organization: $organization);
    }

    /**
     * Determine if the user can delete this invite.
     */
    public function delete(User $user, OrganizationInvite $invite): bool
    {
        return $this->manage(user: $user, organization: $invite->organization);
    }

    /**
     * Determine if the user can "resend" this invite.
     */
    public function resend(User $user, OrganizationInvite $invite): bool
    {
        return $this->manage(user: $user, organization: $invite->organization);
    }

    /**
     * Determine if the user can accept the invite sent to them.
     */
    public function accept(User $user, OrganizationInvite $invite): bool
    {
        return $user->isVerified() && $user->email === $invite->email;
    }

    /**
     * Determine if the user can decline the invite sent to them.
     */
    public function decline(User $user, OrganizationInvite $invite): bool
    {
        return $user->isVerified() && $user->email === $invite->email;
    }

    /**
     * Shared authorization logic for managing organization invites.
     *
     * Used by create and delete checks to validate organization permissions.
     */
    private function manage(User $user, Organization $organization): bool
    {
        return $user->organizationMembership()->hasOrganizationPermission(
            permission: OrganizationPermission::ManageOrganizationMembers,
            organization: $organization
        );
    }
}
