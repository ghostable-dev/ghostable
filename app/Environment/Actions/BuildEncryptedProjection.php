<?php

declare(strict_types=1);

namespace App\Environment\Actions;

use App\Api\V2\Environment\Presenters\EnvironmentKeyPresenter;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Models\EnvironmentSecret;
use JsonException;

class BuildEncryptedProjection
{
    /**
     * Build an encrypted projection bundle for an environment.
     *
     * @param  Environment  $environment  Target environment
     * @param  string[]  $only  Optional allow-list of variable names
     * @param  bool  $includeMeta  Include line_bytes/is_* flags in each entry
     * @param  bool  $includeVersions  Include 'version' in each entry
     * @param  bool  $includeLegacyEnvironmentKey  Append legacy camelCase environmentKey payload
     * @param  DeploymentToken|null  $deploymentToken  Restrict envelope recipients to a specific deployment token
     * @return array{
     *   env: string,
     *   chain: string[],
     *   secrets: array<int, array{
     *     env: string,
     *     name: string,
     *     ciphertext: string,
     *     nonce: string,
     *     alg: string,
     *     aad: array,
     *     claims: array,
     *     version?: int,
     *     meta?: array{ line_bytes?: int, is_vapor_secret?: bool, is_commented?: bool },
     *     updated_at: \Illuminate\Support\Carbon|null,
     *     updated_by: string|null,
     *   }>,
     *   environment_key?: array|null,
     *   environmentKey?: array|null
     * }
     */
    public function __construct(
        private readonly EnvironmentKeyPresenter $environmentKeyPresenter,
    ) {}

    public function handle(
        Environment $environment,
        array $only = [],
        bool $includeMeta = false,
        bool $includeVersions = false,
        bool $includeLegacyEnvironmentKey = false,
        ?DeploymentToken $deploymentToken = null,
    ): array {
        $query = $environment->envSecrets()
            ->with('lastUpdatedBy:id,email')
            ->select([
                'id',
                'environment_id',
                'name',
                'ciphertext',
                'nonce',
                'alg',
                'aad',
                'claims',
                'metadata',
                'line_bytes',
                'is_commented',
                'version',
                'updated_at',
                'last_updated_by',
            ]);

        if (! empty($only)) {
            $query->whereIn('name', $only);
        }

        /** @var EnvironmentSecret[] $secrets */
        $secrets = $query->orderBy('name')->get();

        $rows = [];

        foreach ($secrets as $secret) {
            $entry = [
                'id' => $secret->id,
                'env' => $environment->name,
                'name' => $secret->name,
                'ciphertext' => $secret->ciphertext,
                'nonce' => $secret->nonce,
                'alg' => $secret->alg,
                'aad' => $secret->aad,
                'claims' => $secret->claims,
                'line_bytes' => $secret->line_bytes,
                'is_commented' => (bool) $secret->is_commented,
                'updated_at' => $secret->updated_at,
                'updated_by' => $secret->lastUpdatedBy?->email,
            ];

            if ($includeVersions) {
                $entry['version'] = (int) $secret->version;
            }

            if ($includeMeta) {
                $entry['meta'] = [
                    'line_bytes' => $secret->line_bytes,
                    'is_vapor_secret' => (bool) $secret->is_vapor_secret,
                    'is_commented' => (bool) $secret->is_commented,
                ];
            }

            $rows[] = $entry;
        }

        $environmentKey = $this->resolveActiveEnvironmentKey($environment);

        $resourceEnvironmentKey = null;
        $legacyEnvironmentKey = null;

        if ($environmentKey) {
            $rawRecipients = $environmentKey->envelope?->recipients ?? null;
            $filteredRecipients = $this->filterRecipientsForDeploymentToken($rawRecipients, $deploymentToken);
            $fromEphemeralPublicKey = $this->extractFromEphemeralPublicKey($filteredRecipients);

            $resourceEnvironmentKey = $this->applyEnvelopeMetadataToResource(
                $this->environmentKeyPresenter->present($environmentKey),
                $filteredRecipients,
                $fromEphemeralPublicKey
            );

            if ($includeLegacyEnvironmentKey) {
                $legacyEnvironmentKey = $this->presentLegacyEnvironmentKey(
                    $environmentKey,
                    $filteredRecipients,
                    $fromEphemeralPublicKey
                );
            }
        } elseif ($includeLegacyEnvironmentKey) {
            $legacyEnvironmentKey = null;
        }

        $payload = [
            'env' => $environment->name,
            'chain' => [$environment->name],
            'secrets' => $rows,
            'environment_key' => $resourceEnvironmentKey,
        ];

        if ($includeLegacyEnvironmentKey) {
            $payload['environmentKey'] = $legacyEnvironmentKey;
        }

        return $payload;
    }

    protected function resolveActiveEnvironmentKey(Environment $environment): ?EnvironmentKey
    {
        /** @var EnvironmentKey|null $environmentKey */
        $environmentKey = $environment->keys()
            ->whereNull('rotated_at')
            ->orderByDesc('version')
            ->with('envelope')
            ->first();

        if (! $environmentKey || ! $environmentKey->envelope || $environmentKey->envelope->isInactive()) {
            return null;
        }

        return $environmentKey;
    }

    protected function presentLegacyEnvironmentKey(
        ?EnvironmentKey $environmentKey,
        ?array $recipients,
        ?string $fromEphemeralPublicKey
    ): ?array {
        if (! $environmentKey) {
            return null;
        }

        $envelope = $environmentKey->envelope;

        if (! $envelope) {
            return null;
        }

        $legacyEnvelope = [
            'ciphertext_b64' => $envelope->ciphertext_b64,
            'nonce_b64' => $envelope->nonce_b64,
            'alg' => $envelope->alg,
            'aad_b64' => $envelope->aad_b64,
            'version' => $envelope->version,
            'recipients' => is_array($recipients) ? array_values($recipients) : $recipients,
        ];

        if ($fromEphemeralPublicKey !== null) {
            $legacyEnvelope['from_ephemeral_public_key'] = $fromEphemeralPublicKey;
        }

        return [
            'version' => $environmentKey->version,
            'fingerprint' => $environmentKey->fingerprint,
            'envelope' => $legacyEnvelope,
        ];
    }

    protected function applyEnvelopeMetadataToResource(
        array $presented,
        ?array $recipients,
        ?string $fromEphemeralPublicKey
    ): array {
        $envelopeAttributesPath = 'data.relationships.envelope.data.attributes';

        $attributes = data_get($presented, $envelopeAttributesPath);

        if (! is_array($attributes)) {
            return $presented;
        }

        $normalizedRecipients = is_array($recipients) ? array_values($recipients) : $recipients;

        data_set($presented, $envelopeAttributesPath.'.recipients', $normalizedRecipients);

        if ($fromEphemeralPublicKey !== null) {
            data_set(
                $presented,
                $envelopeAttributesPath.'.from_ephemeral_public_key',
                $fromEphemeralPublicKey
            );
        }

        return $presented;
    }

    protected function filterRecipientsForDeploymentToken(
        ?array $recipients,
        ?DeploymentToken $deploymentToken
    ): ?array {
        if (! is_array($recipients) || $recipients === []) {
            return $recipients;
        }

        if (! $deploymentToken) {
            return array_values($recipients);
        }

        $filtered = array_values(array_filter(
            $recipients,
            function ($recipient) use ($deploymentToken): bool {
                if (! is_array($recipient)) {
                    return false;
                }

                $type = strtolower((string) ($recipient['type'] ?? ''));

                if ($type !== 'deployment') {
                    return false;
                }

                $id = (string) ($recipient['id'] ?? '');

                return $id === (string) $deploymentToken->getKey();
            }
        ));

        return $filtered ?: null;
    }

    protected function extractFromEphemeralPublicKey(?array $recipients): ?string
    {
        if (! is_array($recipients)) {
            return null;
        }

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $encoded = $recipient['edek_b64'] ?? null;

            if (! is_string($encoded) || $encoded === '') {
                continue;
            }

            $normalized = str_starts_with($encoded, 'b64:')
                ? substr($encoded, 4)
                : $encoded;

            $decoded = base64_decode($normalized, true);

            if ($decoded === false || $decoded === '') {
                continue;
            }

            try {
                $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            $ephemeral = $payload['from_ephemeral_public_key'] ?? null;

            if (is_string($ephemeral) && $ephemeral !== '') {
                return $ephemeral;
            }
        }

        return null;
    }
}
