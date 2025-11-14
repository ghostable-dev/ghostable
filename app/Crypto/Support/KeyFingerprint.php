<?php

declare(strict_types=1);

namespace App\Crypto\Support;

final class KeyFingerprint
{
    /**
     * Generates a deterministic fingerprint for a public key.
     */
    public static function fromPublicKey(string $publicKey): string
    {
        $decoded = base64_decode($publicKey, true);

        $hashInput = $decoded === false ? $publicKey : $decoded;

        return hash('sha256', $hashInput);
    }
}
