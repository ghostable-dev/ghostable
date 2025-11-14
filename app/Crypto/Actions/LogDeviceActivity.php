<?php

declare(strict_types=1);

namespace App\Crypto\Actions;

use App\Account\Models\User;
use App\Crypto\Models\Device;
use App\Crypto\Support\KeyFingerprint;

class LogDeviceActivity
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function handle(Device $device, string $event, ?User $user = null, array $context = []): void
    {
        $device->loadMissing('user');

        $description = $context['description'] ?? $this->message($event, $device);
        $resolver = $context['requested_by'] ?? $this->actorProperties($user);
        $ipAddress = $context['ip_address'] ?? request()?->ip();

        unset($context['description'], $context['requested_by'], $context['ip_address']);

        $properties = $context;
        $properties['device'] = $this->deviceProperties($device);

        if ($resolver !== null) {
            $properties['requested_by'] = $resolver;
        }

        $properties['owner'] = [
            'id' => (string) $device->user?->id ?? (string) $device->user_id,
            'email' => $device->user?->email,
        ];

        $properties['ip_address'] = $ipAddress;

        activity('device')
            ->performedOn($device)
            ->causedBy($user)
            ->event($event)
            ->withProperties($properties)
            ->log($description);
    }

    protected function deviceProperties(Device $device): array
    {
        return array_filter([
            'id' => (string) $device->id,
            'name' => $device->name,
            'platform' => $device->platform,
            'app_version' => $device->app_version,
            'public_key_fingerprint' => $device->public_key
                ? KeyFingerprint::fromPublicKey($device->public_key)
                : null,
            'status' => $device->isRevoked() ? 'revoked' : 'active',
        ], static fn ($value) => $value !== null && $value !== '');
    }

    protected function actorProperties(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    protected function message(string $event, Device $device): string
    {
        $name = $device->name ?: $device->platform ?: 'device';

        return match ($event) {
            'created' => "Registered device \"{$name}\" ({$device->platform})",
            'revoked' => "Revoked device \"{$name}\" ({$device->platform})",
            default => ucfirst($event)." device \"{$name}\"",
        };
    }
}
