<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use App\Organization\Models\Invite;

class CreateInvite
{
    public static function handle(
        Organization $organization,
        User $user,
        string $email,
        OrganizationRole $role
    ): Invite {
        $invite = new Invite;
        $invite->organization()->associate($organization);
        $invite->user()->associate($user);
        $invite->email = $email;
        $invite->role = $role;
        $invite->save();

        return $invite;
    }
}
