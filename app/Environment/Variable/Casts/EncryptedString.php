<?php

namespace App\Environment\Variable\Casts;

use App\Environment\Models\Environment;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EncryptedString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $environmentId = $model->environment_id
            ?? ($attributes['environment_id'] ?? null);

        $environment = $environmentId
            ? Environment::find($environmentId)
            : null;

        if (! $environment) {
            return null;
        }

        try {
            return $environment->encrypter()->decryptString($value);
        } catch (\Throwable $e) {
            Log::warning('Environment variable decryption failed', [
                'environment_id' => $environmentId,
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

        $environmentId = $model->environment_id
            ?? ($attributes['environment_id'] ?? null);

        $environment = $environmentId
            ? Environment::find($environmentId)
            : null;

        return $environment
            ? $environment->encrypter()->encryptString((string) $value)
            : (string) $value;
    }
}
