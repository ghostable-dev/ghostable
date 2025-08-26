<?php

declare(strict_types=1);

namespace App\Environment\Versioning\Casts;

use App\Core\Casts\EncryptedString;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Versioning\Models\EnvironmentVariableVersion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use InvalidArgumentException;
use LogicException;

class EncryptedVariableVersionValue extends EncryptedString
{
    protected function encrypterForModel(Model $model): Encrypter
    {
        if (! $model instanceof EnvironmentVariableVersion) {
            throw new InvalidArgumentException('EncryptedVariableVersionValue cast expects EnvironmentVariableVersion model.');
        }
        
        $environment = ResolveEnvironment::onceWithContext($model->variable?->environment_id);

        if (! $environment) {
            throw new LogicException('Environment is missing; cannot resolve encrypter().');
        }

        return $environment->encrypter();
    }
}
