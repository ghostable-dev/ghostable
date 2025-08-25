<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationInvite;

class CreateOrganizationInvite
{
    public static function handle(
        Organization $organization,
        User $user,
        string $email,
        OrganizationRole $role
    ): OrganizationInvite {
        $invite = new OrganizationInvite;
        $invite->organization()->associate($organization);
        $invite->user()->associate($user);
        $invite->email = $email;
        $invite->role = $role;
        $invite->save();

        return $invite;
    }
}
