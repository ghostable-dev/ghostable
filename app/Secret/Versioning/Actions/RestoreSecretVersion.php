<?php

namespace App\Secret\Versioning\Actions;

use App\Account\Models\User;
use App\Secret\Versioning\Models\SecretVersion;

class RestoreSecretVersion
{
    public function __construct(
        protected CreateSecretVersion $createVersion
    ) {}

    public function handle(
        SecretVersion $version,
        ?User $restoredBy = null
    ): void {
        $secret = $version->secret;

        $secret->update([
            'name' => $version->name,
            'type' => $version->type,
            'value' => $version->value,
            'metadata' => $version->metadata,
            'last_updated_at' => now(),
            'last_updated_by' => $restoredBy?->id,
        ]);

        $secret->createVersionBy($restoredBy);

        $secret->logActivity('restored', user: $restoredBy);
    }
}
