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
            'value_encrypted' => $secret->value_encrypted,
            'metadata' => $secret->metadata,
            'version' => $secret->versions()->max('version') + 1,
        ]);

        $version->changedBy()->associate($changedBy);
        $version->secret()->associate($secret);
        $version->save();

        return $version;
    }
}
