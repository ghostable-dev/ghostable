<?php

namespace App\Team\Actions;

use App\Team\Models\Team;
use App\Account\Models\User;

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
