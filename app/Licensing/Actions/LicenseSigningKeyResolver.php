<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use RuntimeException;

class LicenseSigningKeyResolver
{
    public const ALGORITHM = 'Ed25519';

    /**
     * @return array{key_id: string, private_key: string, public_key: string}
     */
    public function activeSigningKey(): array
    {
        $keyId = (string) config('license.signing.active_key_id', '');
        $keys = $this->configuredKeys();

        if ($keyId !== '' && isset($keys[$keyId])) {
            $privateKey = $this->privateKey($keys[$keyId], $keyId);

            return [
                'key_id' => $keyId,
                'private_key' => $privateKey,
                'public_key' => $this->publicKeyFromConfigOrSecret($keys[$keyId], $privateKey, $keyId),
            ];
        }

        if (app()->isProduction()) {
            throw new RuntimeException('No active Ghostable license signing key is configured.');
        }

        return $this->localDevelopmentSigningKey();
    }

    public function publicKeyFor(string $keyId): string
    {
        $keys = $this->configuredKeys();

        if (isset($keys[$keyId])) {
            return $this->decodeKey((string) ($keys[$keyId]['public_key'] ?? ''), SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, "public key [{$keyId}]");
        }

        if (! app()->isProduction() && $keyId === $this->localDevelopmentKeyId()) {
            return $this->localDevelopmentSigningKey()['public_key'];
        }

        throw new RuntimeException("Unknown Ghostable license signing key [{$keyId}].");
    }

    public function localDevelopmentKeyId(): string
    {
        return 'local-dev';
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function configuredKeys(): array
    {
        $keys = config('license.signing.keys', []);

        return is_array($keys) ? $keys : [];
    }

    /**
     * @param  array<string, string>  $key
     */
    private function privateKey(array $key, string $keyId): string
    {
        $privateKey = (string) ($key['private_key'] ?? '');
        $decoded = base64_decode($privateKey, true);

        if ($decoded === false) {
            throw new RuntimeException("Invalid base64 private key [{$keyId}].");
        }

        if (strlen($decoded) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            return $decoded;
        }

        if (strlen($decoded) === SODIUM_CRYPTO_SIGN_SEEDBYTES) {
            return sodium_crypto_sign_secretkey(sodium_crypto_sign_seed_keypair($decoded));
        }

        throw new RuntimeException("Invalid Ed25519 private key length [{$keyId}].");
    }

    /**
     * @param  array<string, string>  $key
     */
    private function publicKeyFromConfigOrSecret(array $key, string $secretKey, string $keyId): string
    {
        if (array_key_exists('public_key', $key)) {
            return $this->decodeKey((string) $key['public_key'], SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, "public key [{$keyId}]");
        }

        return sodium_crypto_sign_publickey_from_secretkey($secretKey);
    }

    private function decodeKey(string $key, int $expectedBytes, string $label): string
    {
        $decoded = base64_decode($key, true);

        if ($decoded === false || strlen($decoded) !== $expectedBytes) {
            throw new RuntimeException("Invalid Ed25519 {$label}.");
        }

        return $decoded;
    }

    /**
     * @return array{key_id: string, private_key: string, public_key: string}
     */
    private function localDevelopmentSigningKey(): array
    {
        $seed = hash('sha256', (string) config('app.key', 'ghostable-local-development-license-signing-key'), true);
        $keypair = sodium_crypto_sign_seed_keypair($seed);
        $privateKey = sodium_crypto_sign_secretkey($keypair);

        return [
            'key_id' => $this->localDevelopmentKeyId(),
            'private_key' => $privateKey,
            'public_key' => sodium_crypto_sign_publickey($keypair),
        ];
    }
}
