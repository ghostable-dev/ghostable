<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Auth\Actions\LogAccountSecurityActivity;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Events\MemberRoleChanged;
use App\Organization\Models\Organization;

class UpdateOrganizationMemberRole
{
    public static function handle(
        User $member,
        Organization $organization,
        OrganizationRole $role,
        ?User $actor = null
    ): void {
        // Ensure member is already part of the organization
        if (! $member->organizations()->where('organization_id', $organization->id)->exists()) {
            throw new \RuntimeException('User is not a member of this organization.');
        }

        $currentRole = $member->organizationMembership()
            ->getMembershipForOrganization($organization)
            ?->pivot
            ?->role;

        // Prepare pivot update attributes
        $attributes = [
            'role' => $role->value,
        ];

        // Update the pivot record
        $member->organizations()->updateExistingPivot($organization->id, $attributes);

        if ($currentRole && $currentRole !== $role) {
            app(LogAccountSecurityActivity::class)->roleChanged(
                member: $member,
                fromRole: $currentRole->value,
                toRole: $role->value,
                context: [
                    'organization' => [
                        'id' => (string) $organization->id,
                        'name' => $organization->name,
                    ],
                ],
                actor: $actor
            );
        }

        MemberRoleChanged::dispatch($organization, $member);
    }
}
