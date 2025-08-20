<?php

namespace App\Secret\Actions;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;

class CreateSecret
{
    public function handle(
        Environment $environment,
        string $name,
        SecretType $type,
        string $value,
        ?array $metadata,
        User $createdBy
    ): Secret {
        $secret = new Secret([
            'name' => $name,
            'type' => $type,
            'metadata' => $metadata,
            'last_updated_at' => now(),
        ]);

        $secret->value = $value;
        $secret->environment()->associate($environment);
        $secret->createdBy()->associate($createdBy);
        $secret->lastUpdatedBy()->associate($createdBy);
        $secret->save();

        $secret->createVersionBy($createdBy);

        $secret->logActivity('created', user: $createdBy);

        return $secret;
    }
}
