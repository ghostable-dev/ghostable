<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

class SignLicenseEntitlement
{
    public function __construct(
        private CanonicalJson $canonicalJson,
        private LicenseSigningKeyResolver $keys
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{payload: array<string, mixed>, signature: string, key_id: string, algorithm: string}
     */
    public function execute(array $payload): array
    {
        $key = $this->keys->activeSigningKey();
        $signature = sodium_crypto_sign_detached($this->canonicalJson->encode($payload), $key['private_key']);

        return [
            'payload' => $payload,
            'signature' => base64_encode($signature),
            'key_id' => $key['key_id'],
            'algorithm' => LicenseSigningKeyResolver::ALGORITHM,
        ];
    }
}
