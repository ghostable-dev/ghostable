<?php

declare(strict_types=1);

namespace App\Secret\Versioning\Casts;

use App\Core\Casts\EncryptedString;
use App\Secret\Models\Secret;
use App\Secret\Versioning\Models\SecretVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use InvalidArgumentException;
use LogicException;

class EncryptedSecretVersionValue extends EncryptedString
{
    protected function encrypterForModel(Model $model): Encrypter
    {
        if (! $model instanceof SecretVersion) {
            throw new InvalidArgumentException('EncryptedSecretVersionValue cast expects SecretVersion model.');
        }

        $secret = Secret::select('id', 'environment_id', 'dek_wrapped', 'kek_salt')
            ->find($model->secret_id);

        if (! $secret) {
            throw new LogicException('Secret is missing; cannot resolve encrypter().');
        }

        return $secret->encrypter();
    }
}
