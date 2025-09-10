<?php

namespace App\Organization\Rules;

use App\Account\Rules\UserRules;
use App\Organization\Models\Organization;

class InviteRules
{
    public static function createRules(Organization $organization): array
    {
        return [
            'email' => array_merge(
                UserRules::emailRules(),
                [new UniqueInvite($organization)],
                [new UniqueEmailForOrganization($organization)],
            ),
            'role' => [
                'required',
                new ValidOrganizationRole,
                new WithinOrganizationUserCap($organization),
            ],
        ];
    }
}
