<?php

namespace App\Organization\Events;

use App\Account\Models\User;
use App\Organization\Models\Organization;

class OrganizationCreated extends OrganizationEvent
{
    public function __construct(public Organization $organization, public User $owner)
    {
        parent::__construct($organization);
    }
}
