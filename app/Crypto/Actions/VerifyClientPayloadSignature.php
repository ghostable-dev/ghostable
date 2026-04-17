<?php

declare(strict_types=1);

namespace App\Crypto\Actions;

use App\Crypto\Models\Device;
use Illuminate\Validation\ValidationException;
use JsonException;
use RuntimeException;

final class VerifyClientPayloadSignature
{
    private const SIGNATURE_LENGTH_BYTES = SODIUM_CRYPTO_SIGN_BYTES;

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

        $signature = $this->resolveSignature(
            signatureBase64: $signatureBase64,
            attributePath: $attributePath
        );
        $publicSigningKey = $this->resolvePublicSigningKey($device);

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

        $this->verifyDetachedSignature(
            signature: $signature,
            payloadJson: $payloadJson,
            publicSigningKey: $publicSigningKey,
            attributePath: $attributePath,
            contextLabel: $contextLabel
        );
    }

    public function handleRawPayload(
        string $payloadJson,
        string $signatureBase64,
        Device $device,
        string $attributePath,
        ?string $contextLabel = null
    ): void {
        $signature = $this->resolveSignature(
            signatureBase64: $signatureBase64,
            attributePath: $attributePath
        );
        $publicSigningKey = $this->resolvePublicSigningKey($device);

        $this->verifyDetachedSignature(
            signature: $signature,
            payloadJson: $payloadJson,
            publicSigningKey: $publicSigningKey,
            attributePath: $attributePath,
            contextLabel: $contextLabel
        );
    }

    /**
     * @throws RuntimeException
     */
    private function verifyDetachedSignature(
        string $signature,
        string $payloadJson,
        string $publicSigningKey,
        string $attributePath,
        ?string $contextLabel
    ): void {
        if (! sodium_crypto_sign_verify_detached($signature, $payloadJson, $publicSigningKey)) {
            $label = $contextLabel !== null ? "secret \"{$contextLabel}\"" : 'payload';

            throw ValidationException::withMessages([
                $attributePath => ["Invalid signature detected for {$label}."],
            ]);
        }
    }

    private function resolveSignature(string $signatureBase64, string $attributePath): string
    {
        $signature = base64_decode($signatureBase64, true);

        if ($signature === false) {
            throw ValidationException::withMessages([
                $attributePath => ['Invalid signature format.'],
            ]);
        }

        if (strlen($signature) !== self::SIGNATURE_LENGTH_BYTES) {
            throw ValidationException::withMessages([
                $attributePath => ['Invalid signature format.'],
            ]);
        }

        return $signature;
    }

    private function resolvePublicSigningKey(Device $device): string
    {
        $publicSigningKeyB64 = $device->public_signing_key;

        if (! is_string($publicSigningKeyB64) || $publicSigningKeyB64 === '') {
            throw new RuntimeException('Device public signing key missing.');
        }

        $publicSigningKey = base64_decode($publicSigningKeyB64, true);

        if ($publicSigningKey === false) {
            throw new RuntimeException('Device public signing key is invalid.');
        }

        return $publicSigningKey;
    }
}
