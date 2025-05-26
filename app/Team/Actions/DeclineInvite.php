<?php

namespace App\Team\Actions;

use App\Team\Models\TeamInvite;

class DeclineInvite
{
    public function handle(TeamInvite $invite): void
    {
        $invite->delete();
    }
}