<?php

namespace App\Organization\Actions;

use App\Organization\Models\Invite;

class DeclineInvite
{
    public function handle(Invite $invite): void
    {
        $invite->delete();
    }
}
