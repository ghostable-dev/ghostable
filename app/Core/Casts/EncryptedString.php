<?php

declare(strict_types=1);

namespace App\Core\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Log;
use Stringable;
use Throwable;

/**
 * Encrypts/decrypts a string attribute at-rest using Laravel's encrypter.
 *
 * Store this cast on a model attribute to ensure values are transparently
 * encrypted when saved and decrypted when read.
 *
 * Notes:
 * - Returns null if encryption/decryption fails (and logs a warning).
 * - Override encrypterForModel() to supply per-environment keys.
 */
class EncryptedString implements CastsAttributes
{
    /**
     * Decrypt the stored value to a string.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        try {
            $encrypter = $this->encrypterForModel($model);
            /** @var string $ciphertext */
            $ciphertext = (string) $value;

            return $encrypter->decryptString($ciphertext);
        } catch (Throwable $error) {
            $this->logFailure('decrypt', $error, $model, $key);

            return null;
        }
    }

    /**
     * Encrypt the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        // Only allow scalar-ish values to avoid surprises.
        if (! is_scalar($value) && ! $value instanceof Stringable) {
            $this->logFailure('encrypt', new \InvalidArgumentException('Non-string value provided.'), $model, $key);

            return null;
        }

        try {
            $encrypter = $this->encrypterForModel($model);
            $plaintext = (string) $value;

            return $encrypter->encryptString($plaintext);
        } catch (Throwable $error) {
            $this->logFailure('encrypt', $error, $model, $key);

            return null;
        }
    }

    /**
     * Resolve the encrypter to use for the given model.
     * Override to supply a per-model/per-tenant key.
     */
    protected function encrypterForModel(Model $model): Encrypter
    {
        /** @var Encrypter $enc */
        $enc = app(Encrypter::class); // default application encrypter (APP_KEY)

        return $enc;
    }

    /**
     * Centralized, safe logging for failures (does NOT log plaintext).
     *
     * @param  'encrypt'|'decrypt'  $phase
     */
    protected function logFailure(string $phase, Throwable $error, Model $model, string $attribute): void
    {
        Log::warning('EncryptedString '.$phase.'ion failed', [
            'phase' => $phase,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'attribute' => $attribute,
            'exception_class' => $error::class,
            'exception_msg' => $error->getMessage(),
        ]);
    }
}
