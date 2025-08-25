<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Models\Organization;

class CreatePersonalOrganization
{
    public function handle(User $owner): Organization
    {
        return app(CreateOrganization::class)->handle(
            name: 'Personal',
            owner: $owner,
            personal: true
        );
    }
}
