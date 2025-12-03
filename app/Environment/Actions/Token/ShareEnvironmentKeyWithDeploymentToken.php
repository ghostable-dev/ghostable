<?php

declare(strict_types=1);

namespace App\Environment\Actions\Token;

use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use Illuminate\Support\Facades\Log;

class ShareEnvironmentKeyWithDeploymentToken
{
    public function handle(DeploymentToken $deploymentToken, ?array $recipient = null): void
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

        $normalizedRecipient = $this->normalizeRecipient($recipient, $deploymentToken);

        if (! $normalizedRecipient) {
            Log::info('Skipped sharing environment key with deployment token: missing recipient payload.', [
                'deployment_token_id' => (string) $deploymentToken->getKey(),
                'environment_id' => (string) $environment->getKey(),
            ]);

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

        $filteredRecipients[] = $normalizedRecipient;

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

    private function normalizeRecipient(?array $recipient, DeploymentToken $deploymentToken): ?array
    {
        if (! is_array($recipient)) {
            return null;
        }

        $encoded = $recipient['edek_b64'] ?? null;

        if (! is_string($encoded) || $encoded === '') {
            return null;
        }

        $normalized = $recipient;
        $normalized['type'] = 'deployment';
        $normalized['id'] = (string) $deploymentToken->getKey();
        $normalized['edek_b64'] = $encoded;

        return $normalized;
    }
}
