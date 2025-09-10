<?php

namespace App\Secret\Versioning\Actions;

use App\Account\Models\User;
use App\Secret\Models\Secret;
use App\Secret\Versioning\Models\SecretVersion;

class CreateSecretVersion
{
    public function handle(
        Secret $secret,
        ?User $changedBy = null
    ): SecretVersion {
        $version = new SecretVersion([
            'name' => $secret->name,
            'type' => $secret->type,
            'metadata' => $secret->metadata,
            'version' => $secret->versions()->max('version') + 1,
            'secret_id' => $secret->id,
        ]);

        $version->changedBy()->associate($changedBy);
        $version->value = $secret->value;
        $version->save();

        return $version;
    }
}
