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
        ]);

        $secret->value = $value;
        $secret->owner()->associate($owner);
        $secret->createdBy()->associate($createdBy);
        $secret->save();

        $secret->logActivity('created', user: $createdBy);

        return $secret;
    }
}
