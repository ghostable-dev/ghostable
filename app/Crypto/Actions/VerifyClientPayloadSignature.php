<?php

declare(strict_types=1);

namespace App\Crypto\Actions;

use App\Crypto\Models\Device;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;

final class VerifyClientPayloadSignature
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws RuntimeException
     * @throws ValidationException
     */
    public function handle(
        array $payload,
        string $signatureBase64,
        Device $device,
        string $attributePath,
        ?string $contextLabel = null
    ): void {
        if ($signatureBase64 === '') {
            throw ValidationException::withMessages([
                $attributePath => ['Missing client signature.'],
            ]);
        }

        $signature = base64_decode($signatureBase64, true);

        if ($signature === false) {
            throw ValidationException::withMessages([
                $attributePath => ['Invalid signature format.'],
            ]);
        }

        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw ValidationException::withMessages([
                $attributePath => ['Invalid signature format.'],
            ]);
        }

        $publicSigningKeyB64 = $device->public_signing_key;

        if (! is_string($publicSigningKeyB64) || $publicSigningKeyB64 === '') {
            throw new RuntimeException('Device public signing key missing.');
        }

        $publicSigningKey = base64_decode($publicSigningKeyB64, true);

        if ($publicSigningKey === false) {
            throw new RuntimeException('Device public signing key is invalid.');
        }

        try {
            $payloadJson = json_encode(
                $payload,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Unable to normalize payload for signature verification.',
                previous: $exception
            );
        }

        if (! sodium_crypto_sign_verify_detached($signature, $payloadJson, $publicSigningKey)) {
            $label = $contextLabel !== null ? "secret \"{$contextLabel}\"" : 'payload';

            throw ValidationException::withMessages([
                $attributePath => ["Invalid signature detected for {$label}."],
            ]);
        }
    }
}
