<?php

declare(strict_types=1);

namespace App\Api\V2\Environment\Presenters;

use App\Crypto\Models\Envelope;
use App\Environment\Models\EnvironmentKey;
use Illuminate\Support\Carbon;
use JsonException;

final class EnvironmentKeyPresenter
{
    public function present(EnvironmentKey $environmentKey): array
    {
        return [
            'data' => [
                'type' => 'environment-keys',
                'id' => (string) $environmentKey->getKey(),
                'attributes' => [
                    'environment_id' => (string) $environmentKey->environment_id,
                    'version' => $environmentKey->version,
                    'fingerprint' => $environmentKey->fingerprint,
                    'created_by_device_id' => $environmentKey->created_by_device_id
                        ? (string) $environmentKey->created_by_device_id
                        : null,
                    'rotated_at' => $environmentKey->rotated_at?->toIso8601String(),
                    'created_at' => $environmentKey->created_at?->toIso8601String(),
                    'updated_at' => $environmentKey->updated_at?->toIso8601String(),
                ],
                'relationships' => [
                    'envelope' => [
                        'data' => $this->presentEnvelope($environmentKey->envelope),
                    ],
                    'envelopes' => [
                        'data' => $this->presentEnvelopes($environmentKey->envelope),
                    ],
                ],
            ],
        ];
    }

    public function presentEnvelope(?Envelope $envelope): ?array
    {
        if (! $envelope) {
            return null;
        }

        $recipients = $envelope->recipients;

        if (is_array($recipients)) {
            $recipients = array_map(fn ($recipient) => $this->normalizeRecipient($recipient), $recipients);
        }

        $fromEphemeralPublicKey = $this->extractFromEphemeralPublicKey($recipients);

        return [
            'type' => 'encrypted-envelopes',
            'id' => (string) $envelope->getKey(),
            'attributes' => [
                'ciphertext_b64' => $envelope->ciphertext_b64,
                'nonce_b64' => $envelope->nonce_b64,
                'alg' => $envelope->alg,
                'aad_b64' => $envelope->aad_b64,
                'recipients' => $recipients,
                'version' => $envelope->version,
                'created_at' => $envelope->created_at?->toIso8601String(),
                'revoked_at' => $envelope->revoked_at?->toIso8601String(),
                'from_ephemeral_public_key' => $fromEphemeralPublicKey,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function presentEnvelopes(?Envelope $envelope): array
    {
        if (! $envelope) {
            return [];
        }

        $recipients = $envelope->recipients;

        if (! is_array($recipients)) {
            return [];
        }

        $items = [];

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $type = strtolower((string) ($recipient['type'] ?? ''));

            if ($type !== 'device') {
                continue;
            }

            $payload = $this->decodeRecipientPayload($recipient['edek_b64'] ?? null);

            if (! is_array($payload)) {
                continue;
            }

            $deviceId = (string) ($recipient['id'] ?? '');

            $items[] = [
                'type' => 'encrypted-envelopes',
                'id' => $deviceId,
                'attributes' => [
                    'device_id' => $deviceId,
                    'ciphertext_b64' => $payload['ciphertext_b64'] ?? null,
                    'nonce_b64' => $payload['nonce_b64'] ?? null,
                    'alg' => $payload['alg'] ?? null,
                    'aad_b64' => $payload['aad_b64'] ?? null,
                    'version' => $payload['version'] ?? null,
                    'from_ephemeral_public_key' => $payload['from_ephemeral_public_key'] ?? null,
                    'expires_at' => $payload['expires_at'] ?? null,
                ],
            ];
        }

        return array_values($items);
    }

    private function normalizeRecipient(mixed $recipient): mixed
    {
        if (! is_array($recipient)) {
            return $recipient;
        }

        $type = $recipient['type'] ?? null;

        if (is_string($type)) {
            $recipient['type'] = match (strtolower($type)) {
                'deployment',
                'deployment-token',
                'deployment_tokens',
                'deploymenttoken',
                'deploymenttokens' => 'deployment',
                default => $type,
            };
        }

        return $recipient;
    }

    private function extractFromEphemeralPublicKey(mixed $recipients): ?string
    {
        if (! is_array($recipients)) {
            return null;
        }

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $payload = $this->decodeRecipientPayload($recipient['edek_b64'] ?? null);

            if (! is_array($payload)) {
                continue;
            }

            $ephemeral = $payload['from_ephemeral_public_key'] ?? null;

            if (is_string($ephemeral) && $ephemeral !== '') {
                return $ephemeral;
            }
        }

        return null;
    }

    private function decodeRecipientPayload(mixed $encoded): ?array
    {
        if (! is_string($encoded) || $encoded === '') {
            return null;
        }

        $normalized = str_starts_with($encoded, 'b64:')
            ? substr($encoded, 4)
            : $encoded;

        $decoded = base64_decode($normalized, true);

        if ($decoded === false || $decoded === '') {
            return null;
        }

        try {
            $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        if (isset($payload['expires_at']) && is_string($payload['expires_at'])) {
            try {
                $payload['expires_at'] = Carbon::parse($payload['expires_at'])->toIso8601String();
            } catch (\Throwable) {
                // ignore invalid date, leave as string
            }
        }

        return $payload;
    }
}
