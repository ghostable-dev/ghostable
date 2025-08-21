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

        $encrypter = $environment?->encrypter() ?? app('encrypter');

        try {
            return $encrypter->decryptString($value);
        } catch (\Throwable $e) {
            // Fallback to the application key to support legacy data that
            // may still be encrypted with the global encrypter.
            try {
                return app('encrypter')->decryptString($value);
            } catch (\Throwable $e2) {
                Log::warning('Environment variable decryption failed', [
                    'environment_id' => $environmentId,
                    'model_type' => $model::class,
                    'model_id' => $model->getKey(),
                    'exception_class' => get_class($e2),
                    'exception_msg' => $e2->getMessage(),
                ]);

                return null;
            }
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

        $encrypter = $environment?->encrypter() ?? app('encrypter');

        return $encrypter->encryptString((string) $value);
    }
}
