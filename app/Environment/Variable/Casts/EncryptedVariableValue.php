<?php

declare(strict_types=1);

namespace App\Environment\Variable\Casts;

use App\Core\Casts\EncryptedString;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Variable\Models\EnvironmentVariable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use InvalidArgumentException;
use LogicException;

class EncryptedVariableValue extends EncryptedString
{
    protected function encrypterForModel(Model $model): Encrypter
    {
        if (! $model instanceof EnvironmentVariable) {
            throw new InvalidArgumentException('EncryptedVariableValue cast expects EnvironmentVariable model.');
        }

        $environment = ResolveEnvironment::onceWithContext($model->environment_id);

        if (! $environment) {
            throw new LogicException('Environment is missing; cannot resolve encrypter().');
        }

        return $environment->encrypter();
    }
}
