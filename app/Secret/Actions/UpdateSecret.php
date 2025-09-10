<?php

namespace App\Secret\Actions;

use App\Account\Models\User;
use App\Secret\Models\Secret;

class UpdateSecret
{
    public function handle(
        Secret $secret,
        string $value,
        ?array $metadata,
        ?User $updatedBy = null
    ): Secret {
        $secret->update([
            'metadata' => $metadata,
            'last_updated_at' => now(),
            'last_updated_by' => $updatedBy?->id,
        ]);

        $secret->value = $value;
        $secret->save();

        $secret->createVersionBy($updatedBy);

        $secret->logActivity('updated', user: $updatedBy);

        return $secret;
    }
}
