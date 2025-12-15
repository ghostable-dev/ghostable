<?php

declare(strict_types=1);

namespace App\Backup\Actions;

use App\Backup\Support\EnvelopeEncryptor;
use App\Crypto\Models\Device;
use App\Environment\Models\Environment;
use App\Project\Models\Project;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

class BuildEnvironmentBackup
{
    public function __construct(
        private readonly EnvelopeEncryptor $encryptor,
    ) {}

    /**
     * Build an encrypted backup envelope. The payload is symmetrically encrypted
     * with a one-time Backup Data Key (BDK). The BDK is envelope-encrypted to
     * the requesting device and an optional organization recovery key.
     *
     * @param  array<string, mixed>  $bundle  The encrypted environment projection
     * @return array<string, mixed>
     */
    public function handle(
        Project $project,
        Environment $environment,
        Device $requestingDevice,
        array $bundle,
        ?string $recoveryPublicKey,
        ?string $recoveryLabel,
        ?string $requestIp = null
    ): array {
        $createdAt = now();
        $backupId = (string) Str::uuid();

        $payload = [
            'version' => 'payload.v1',
            'meta' => [
                'backup_id' => $backupId,
                'project_id' => (string) $project->getKey(),
                'environment_id' => (string) $environment->getKey(),
                'environment' => $environment->name,
                'created_at' => $createdAt->toIso8601String(),
            ],
            'bundle' => $bundle,
        ];

        try {
            $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
            $payloadLength = strlen($payloadJson);
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode backup payload.', previous: $exception);
        }

        $bdk = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES);

        $payloadNonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $payloadAad = json_encode([
            'project_id' => (string) $project->getKey(),
            'environment' => $environment->name,
            'backup_id' => $backupId,
            'created_at' => $createdAt->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        $payloadCiphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $payloadJson,
            $payloadAad,
            $payloadNonce,
            $bdk
        );

        $recipientMeta = [
            'project_id' => (string) $project->getKey(),
            'environment' => $environment->name,
            'backup_id' => $backupId,
            'context' => 'backup.bdk',
            'created_at' => $createdAt->toIso8601String(),
        ];

        $recipients = [
            $this->makeRecipient(
                type: 'device',
                id: (string) $requestingDevice->getKey(),
                label: $requestingDevice->name,
                publicKey: (string) $requestingDevice->public_key,
                bdk: $bdk,
                meta: $recipientMeta
            ),
        ];

        if ($recoveryPublicKey) {
            $recipients[] = $this->makeRecipient(
                type: 'recovery',
                id: 'organization-recovery',
                label: $recoveryLabel ?: 'Organization recovery key',
                publicKey: $recoveryPublicKey,
                bdk: $bdk,
                meta: $recipientMeta
            );
        }

        $integrityHash = base64_encode(hash('sha256', $payloadJson, true));

        sodium_memzero($bdk);
        sodium_memzero($payloadJson);

        return [
            'version' => 'backup.v1',
            'backup_id' => $backupId,
            'created_at' => $createdAt->toIso8601String(),
            'project' => [
                'id' => (string) $project->getKey(),
                'name' => $project->name,
            ],
            'environment' => [
                'id' => (string) $environment->getKey(),
                'name' => $environment->name,
            ],
            'payload' => [
                'alg' => 'xchacha20-poly1305',
                'nonce_b64' => base64_encode($payloadNonce),
                'ciphertext_b64' => base64_encode($payloadCiphertext),
                'aad_b64' => base64_encode($payloadAad),
            ],
            'recipients' => $recipients,
            'integrity' => [
                'sha256_b64' => $integrityHash,
                'payload_bytes' => $payloadLength ?? null,
            ],
            'statistics' => [
                'secret_count' => is_countable($bundle['secrets'] ?? null)
                    ? count($bundle['secrets'])
                    : null,
                'recipient_count' => count($recipients),
            ],
            'environment_key_fingerprint' => data_get(
                $bundle,
                'environment_key.data.attributes.fingerprint'
            ),
            'request' => [
                'ip_address' => $requestIp,
                'device_id' => (string) $requestingDevice->getKey(),
                'device_name' => $requestingDevice->name,
            ],
        ];
    }

    /**
     * @param  array<string, string>  $meta
     * @return array<string, mixed>
     */
    private function makeRecipient(
        string $type,
        string $id,
        ?string $label,
        string $publicKey,
        string $bdk,
        array $meta
    ): array {
        $envelope = $this->encryptor->encrypt($bdk, $publicKey, $meta);

        try {
            $encodedEnvelope = base64_encode(json_encode($envelope, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode backup recipient envelope.', previous: $exception);
        }

        return array_filter([
            'type' => $type,
            'id' => $id,
            'label' => $label,
            'public_key' => $publicKey,
            'edek_b64' => $encodedEnvelope,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
