<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use Throwable;

class VerifyLicenseEntitlement
{
    public function __construct(
        private CanonicalJson $canonicalJson,
        private LicenseSigningKeyResolver $keys
    ) {}

    /**
     * @param  array<string, mixed>  $signedEntitlement
     */
    public function execute(array $signedEntitlement): bool
    {
        if (($signedEntitlement['algorithm'] ?? null) !== LicenseSigningKeyResolver::ALGORITHM) {
            return false;
        }

        if (! is_array($signedEntitlement['payload'] ?? null)) {
            return false;
        }

        if (! is_string($signedEntitlement['key_id'] ?? null) || ! is_string($signedEntitlement['signature'] ?? null)) {
            return false;
        }

        $signature = base64_decode($signedEntitlement['signature'], true);

        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        try {
            $publicKey = $this->keys->publicKeyFor($signedEntitlement['key_id']);
        } catch (Throwable) {
            return false;
        }

        return sodium_crypto_sign_verify_detached(
            $signature,
            $this->canonicalJson->encode($signedEntitlement['payload']),
            $publicKey
        );
    }
}
