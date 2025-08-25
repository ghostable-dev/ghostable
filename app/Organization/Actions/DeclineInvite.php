<?php

namespace App\Organization\Actions;

use App\Organization\Models\OrganizationInvite;

class DeclineInvite
{
    public function handle(OrganizationInvite $invite): void
    {
        $invite->delete();
    }
}
