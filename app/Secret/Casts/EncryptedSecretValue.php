<?php

declare(strict_types=1);

namespace App\Secret\Casts;

use App\Core\Casts\EncryptedString;
use App\Secret\Models\Secret;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use InvalidArgumentException;

class EncryptedSecretValue extends EncryptedString
{
    protected function encrypterForModel(Model $model): Encrypter
    {
        if (! $model instanceof Secret) {
            throw new InvalidArgumentException('EncryptedSecretValue cast expects Secret model.');
        }

        return $model->encrypter();
    }
}
