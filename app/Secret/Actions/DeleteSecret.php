<?php

namespace App\Secret\Actions;

use App\Account\Models\User;
use App\Secret\Models\Secret;

class DeleteSecret
{
    public function handle(Secret $secret, ?User $deletedBy = null): void
    {
        $secret->delete();

        $secret->logActivity('deleted', user: $deletedBy);
    }
}
