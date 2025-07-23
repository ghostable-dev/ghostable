<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Enums\TeamRole;
use App\Team\Events\MemberRoleChanged;
use App\Team\Models\Team;

class UpdateTeamMemberRole
{
    public static function handle(User $member, Team $team, TeamRole $role): void
    {
        // Ensure member is already part of the team
        if (! $member->teams->contains($team->id)) {
            throw new \RuntimeException('User is not a member of this team.');
        }

        // Prepare pivot update attributes
        $attributes = [
            'role' => $role->key,
            'permissions' => null,
        ];

        // Update the pivot record
        $member->teams()->updateExistingPivot($team->id, $attributes);

        MemberRoleChanged::dispatch($team, $member);
    }
}
