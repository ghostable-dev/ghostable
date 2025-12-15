<?php

declare(strict_types=1);

namespace App\Backup\Actions;

use App\Account\Models\User;
use App\Crypto\Models\Device;
use App\Environment\Models\Environment;
use App\Environment\Support\EnvironmentAuditProperties;

class LogEnvironmentBackupCreated
{
    /**
     * @param  array<string, mixed>  $envelope
     */
    public function handle(
        Environment $environment,
        User $user,
        Device $device,
        array $envelope
    ): void {
        $properties = [
            'environment' => EnvironmentAuditProperties::make($environment),
            'requested_by' => [
                'id' => (string) $user->id,
                'email' => $user->email,
            ],
            'device' => [
                'id' => (string) $device->getKey(),
                'name' => $device->name,
                'platform' => $device->platform,
            ],
            'backup' => [
                'backup_id' => $envelope['backup_id'] ?? null,
                'created_at' => $envelope['created_at'] ?? null,
                'recipient_count' => $envelope['statistics']['recipient_count'] ?? null,
                'secret_count' => $envelope['statistics']['secret_count'] ?? null,
                'environment_key_fingerprint' => $envelope['environment_key_fingerprint'] ?? null,
            ],
            'recipients' => $this->extractRecipientSummary($envelope['recipients'] ?? []),
        ];

        if (isset($envelope['request']['ip_address'])) {
            $properties['ip_address'] = $envelope['request']['ip_address'];
        }

        activity('backup')
            ->performedOn($environment)
            ->causedBy($user)
            ->event('created')
            ->withProperties($properties)
            ->log('Environment backup created (envelope only).');
    }

    /**
     * @param  array<int, array<string, mixed>>  $recipients
     * @return array<int, array<string, mixed>>
     */
    private function extractRecipientSummary(array $recipients): array
    {
        return collect($recipients)
            ->map(static function ($recipient) {
                if (! is_array($recipient)) {
                    return null;
                }

                return array_filter([
                    'type' => $recipient['type'] ?? null,
                    'id' => $recipient['id'] ?? null,
                    'label' => $recipient['label'] ?? null,
                    'has_public_key' => isset($recipient['public_key']),
                ], static fn ($value) => $value !== null && $value !== '');
            })
            ->filter()
            ->values()
            ->all();
    }
}
