<?php

declare(strict_types=1);

namespace App\Environment\Actions\Token;

use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use Illuminate\Support\Facades\Log;
use JsonException;
use SodiumException;
use Throwable;

class ShareEnvironmentKeyWithDeploymentToken
{
    public function handle(DeploymentToken $deploymentToken): void
    {
        $environment = $deploymentToken->environment ?? $deploymentToken->environment()->first();

        if (! $environment) {
            return;
        }

        $environmentKey = $this->resolveActiveEnvironmentKey($environment);

        if (! $environmentKey) {
            return;
        }

        $envelope = $environmentKey->envelope;

        if (! $envelope || $envelope->isInactive()) {
            return;
        }

        $environmentKeyBytes = $this->decodeEnvironmentKeyBytes($environment);

        if ($environmentKeyBytes === null) {
            return;
        }

        $recipient = $this->encryptEnvironmentKeyForToken(
            deploymentToken: $deploymentToken,
            environmentKey: $environmentKeyBytes,
            environmentId: (string) $environment->getKey()
        );

        if ($recipient === null) {
            return;
        }

        $existingRecipients = $envelope->recipients;

        if (! is_array($existingRecipients)) {
            $existingRecipients = [];
        }

        $filteredRecipients = array_values(array_filter(
            $existingRecipients,
            function ($entry) use ($deploymentToken): bool {
                if (! is_array($entry)) {
                    return true;
                }

                $type = strtolower((string) ($entry['type'] ?? ''));

                if ($type !== 'deployment') {
                    return true;
                }

                $id = (string) ($entry['id'] ?? '');

                return $id !== (string) $deploymentToken->getKey();
            }
        ));

        $filteredRecipients[] = $recipient;

        $envelope->forceFill([
            'recipients' => $filteredRecipients,
        ])->save();
    }

    private function resolveActiveEnvironmentKey(Environment $environment): ?EnvironmentKey
    {
        /** @var EnvironmentKey|null $environmentKey */
        $environmentKey = $environment->keys()
            ->whereNull('rotated_at')
            ->orderByDesc('version')
            ->with('envelope')
            ->first();

        if (! $environmentKey || ! $environmentKey->envelope) {
            return null;
        }

        return $environmentKey;
    }

    private function decodeEnvironmentKeyBytes(Environment $environment): ?string
    {
        $encryptionKeyString = $environment->encryptionKeyString();

        if (str_starts_with($encryptionKeyString, 'base64:')) {
            $encryptionKeyString = substr($encryptionKeyString, 7);
        }

        $environmentKeyBytes = base64_decode($encryptionKeyString, true);

        if ($environmentKeyBytes === false || $environmentKeyBytes === '') {
            Log::warning('Failed to share environment key: unable to decode environment key string.', [
                'environment_id' => (string) $environment->getKey(),
            ]);

            return null;
        }

        return $environmentKeyBytes;
    }

    /**
     * @return array{type:string,id:string,edek_b64:string}|null
     */
    private function encryptEnvironmentKeyForToken(
        DeploymentToken $deploymentToken,
        string $environmentKey,
        string $environmentId
    ): ?array {
        $encodedPublicKey = $deploymentToken->public_key;
        $publicKey = $encodedPublicKey !== null
            ? base64_decode($encodedPublicKey, true)
            : false;

        if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_BOX_PUBLICKEYBYTES) {
            Log::warning('Failed to share environment key: deployment token public key is invalid.', [
                'deployment_token_id' => (string) $deploymentToken->getKey(),
            ]);

            return null;
        }

        try {
            $ephemeralKeyPair = sodium_crypto_box_keypair();
            $ephemeralSecretKey = sodium_crypto_box_secretkey($ephemeralKeyPair);
            $ephemeralPublicKey = sodium_crypto_box_publickey($ephemeralKeyPair);

            $sharedSecret = sodium_crypto_scalarmult($ephemeralSecretKey, $publicKey);
            $derivedKey = hash_hkdf(
                'sha256',
                $sharedSecret,
                SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
                'ghostable:edek:v1'
            );

            if (! is_string($derivedKey) || strlen($derivedKey) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
                Log::error('Failed to derive deployment token envelope key.', [
                    'deployment_token_id' => (string) $deploymentToken->getKey(),
                    'environment_id' => $environmentId,
                ]);

                return null;
            }

            $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
            $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
                $environmentKey,
                '',
                $nonce,
                $derivedKey
            );

            $payload = [
                'ciphertext_b64' => 'b64:'.base64_encode($ciphertext),
                'nonce_b64' => 'b64:'.base64_encode($nonce),
                'alg' => 'xchacha20-poly1305',
                'aad_b64' => null,
                'from_ephemeral_public_key' => 'b64:'.base64_encode($ephemeralPublicKey),
            ];

            $encodedPayload = 'b64:'.base64_encode(
                json_encode($payload, JSON_THROW_ON_ERROR)
            );
        } catch (SodiumException|JsonException|Throwable $exception) {
            Log::error('Failed to encrypt environment key for deployment token.', [
                'deployment_token_id' => (string) $deploymentToken->getKey(),
                'environment_id' => $environmentId,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        return [
            'type' => 'deployment',
            'id' => (string) $deploymentToken->getKey(),
            'edek_b64' => $encodedPayload,
        ];
    }
}
