<?php

namespace App\Secret\Actions;

use App\Account\Models\User;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use Illuminate\Database\Eloquent\Model;

class CreateSecret
{
    public function handle(
        Model $owner,
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
        $secret->owner()->associate($owner);
        $secret->createdBy()->associate($createdBy);
        $secret->lastUpdatedBy()->associate($createdBy);
        $secret->save();

        $secret->createVersionBy($createdBy);

        $secret->logActivity('created', user: $createdBy);

        return $secret;
    }
}
