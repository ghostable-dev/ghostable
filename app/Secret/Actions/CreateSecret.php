<?php

namespace App\Secret\Actions;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use Illuminate\Encryption\Encrypter;

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
            'environment_id' => $environment->id,
            'last_updated_at' => now(),
            'metadata' => $metadata,
            'name' => $name,
            'type' => $type,
        ]);

        // Initialize a per-secret DEK and wrap it with the environment KEK
        $dek = base64_encode(Encrypter::generateKey(config('app.cipher')));
        $secret->dek_wrapped = $environment->encrypter()->encryptString($dek);
        $secret->kek_salt = $environment->kek_salt;

        $secret->value = $value;
        $secret->createdBy()->associate($createdBy);
        $secret->lastUpdatedBy()->associate($createdBy);
        $secret->save();

        $secret->createVersionBy($createdBy);

        $secret->logActivity('created', user: $createdBy);

        return $secret;
    }
}
