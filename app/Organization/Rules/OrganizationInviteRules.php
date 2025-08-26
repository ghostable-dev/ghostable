<?php

namespace App\Organization\Rules;

use App\Account\Rules\UserRules;
use App\Organization\Models\Organization;

class OrganizationInviteRules
{
    public static function createRules(Organization $organization): array
    {
        return [
            'email' => array_merge(
                UserRules::emailRules(),
                [new UniqueOrganizationInvite($organization)],
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
