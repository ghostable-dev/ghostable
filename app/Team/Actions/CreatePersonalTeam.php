<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Models\Team;

class CreatePersonalTeam
{
    public function handle(User $owner): Team
    {
        return app(CreateTeam::class)->handle(
            name: 'Personal',
            owner: $owner,
            personal: true
        );
    }
}
