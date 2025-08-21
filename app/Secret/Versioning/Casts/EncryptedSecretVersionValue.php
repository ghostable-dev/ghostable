<?php

declare(strict_types=1);

namespace App\Secret\Versioning\Casts;

use App\Core\Casts\EncryptedString;
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

        $environment = $model->secret?->environment;

        if (! $environment) {
            throw new LogicException('Environment is missing; cannot resolve encrypter().');
        }

        return $environment->encrypter();
    }
}
