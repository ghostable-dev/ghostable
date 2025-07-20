<?php

namespace App\Secret\Actions;

use App\Account\Models\User;
use App\Secret\Models\Secret;

class DeleteSecret
{
    public function handle(Secret $secret, ?User $deletedBy = null): void
    {
        $secret->update([
            'last_updated_at' => now(),
            'last_updated_by' => $deletedBy?->id,
        ]);

        $secret->createVersionBy($deletedBy);

        $secret->delete();

        $secret->logActivity('deleted', user: $deletedBy);
    }
}
