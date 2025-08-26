<?php

namespace App\Organization\Events;

use App\Account\Models\User;
use App\Organization\Models\Organization;

class MemberEvent extends OrganizationEvent
{
    public function __construct(public Organization $organization, public User $user)
    {
        parent::__construct($organization);
    }
}
