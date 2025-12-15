<?php

declare(strict_types=1);

namespace App\Backup\Support;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Builds an encrypted envelope compatible with the CLI's EncryptedEnvelope format.
 *
 * The envelope uses X25519 for ECDH, HKDF-SHA256 for key derivation, and
 * XChaCha20-Poly1305 for AEAD encryption. The caller is responsible for
 * providing a 32-byte plaintext (e.g., a data key) and a recipient's public key.
 */
class EnvelopeEncryptor
{
    private const HKDF_INFO = 'ghostable:v1:envelope';

    /**
     * @param  string  $plaintext  Raw bytes to encrypt (e.g., the BDK)
     * @param  string  $recipientPublicKeyBase64  Base64 encoded X25519 public key
     * @param  array<string, string>  $meta  Optional associated data, authenticated but not encrypted
     * @return array<string, mixed>
     */
    public function encrypt(string $plaintext, string $recipientPublicKeyBase64, array $meta = []): array
    {
        $recipientPublicKey = base64_decode($recipientPublicKeyBase64, true);

        if ($recipientPublicKey === false || strlen($recipientPublicKey) !== SODIUM_CRYPTO_KX_PUBLICKEYBYTES) {
            throw new RuntimeException('Invalid recipient public key.');
        }

        $ephemeralKeypair = sodium_crypto_kx_keypair();
        $ephemeralSecretKey = sodium_crypto_kx_secretkey($ephemeralKeypair);
        $ephemeralPublicKey = sodium_crypto_kx_publickey($ephemeralKeypair);

        $sharedSecret = sodium_crypto_scalarmult($ephemeralSecretKey, $recipientPublicKey);

        $aeadKey = hash_hkdf(
            'sha256',
            $sharedSecret,
            SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
            self::HKDF_INFO,
            ''
        );

        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $aad = $meta === [] ? '' : json_encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext,
            $aad ?: '',
            $nonce,
            $aeadKey
        );

        sodium_memzero($sharedSecret);
        sodium_memzero($aeadKey);

        return array_filter([
            'id' => (string) Str::uuid(),
            'version' => 'v1',
            'alg' => 'XChaCha20-Poly1305+HKDF-SHA256',
            'to_device_public_key' => $recipientPublicKeyBase64,
            'from_ephemeral_public_key' => base64_encode($ephemeralPublicKey),
            'nonce_b64' => base64_encode($nonce),
            'ciphertext_b64' => base64_encode($ciphertext),
            'created_at' => now()->toIso8601String(),
            'meta' => $meta ?: null,
        ], static fn ($value) => $value !== null);
    }
}
