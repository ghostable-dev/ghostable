<?php

namespace App\Environment\Variable\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class EncryptedString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            Log::warning('Environment variable decryption failed — possible tampered or mismatched environment ancestry.', [
                'target_env_id' => $this->targetEnvironment->id ?? null,
                'target_env_name' => $this->targetEnvironment->name ?? null,
                'variable_env_id' => $this->variable->environment->id ?? null,
                'variable_env_name' => $this->variable->environment->name ?? null,
                'variable_key' => $this->variable->key ?? $key,
                'model_type' => $model::class,
                'model_id' => $model->getKey(),
                'exception_class' => get_class($e),
                'exception_msg' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }
}
