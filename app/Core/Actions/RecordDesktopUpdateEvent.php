<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Account\Models\User;
use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Models\DesktopUpdateEvent;
use App\Crypto\Models\Device;

final class RecordDesktopUpdateEvent
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function handle(
        DesktopUpdateEventType $eventType,
        DesktopUpdateSource $source,
        string $channel,
        ?string $releaseVersion = null,
        ?string $releaseShortVersion = null,
        ?string $currentVersion = null,
        ?string $fromVersion = null,
        ?string $updateCycleId = null,
        ?Device $device = null,
        ?User $user = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        array $metadata = [],
    ): DesktopUpdateEvent {
        $resolvedUser = $user ?? $device?->user;
        $normalizedIp = $this->normalizeIpAddress($ipAddress);

        return DesktopUpdateEvent::query()->create([
            'event_type' => $eventType,
            'source' => $source,
            'channel' => trim($channel) === '' ? 'stable' : trim($channel),
            'release_version' => $this->nullableString($releaseVersion),
            'release_short_version' => $this->nullableString($releaseShortVersion),
            'current_version' => $this->nullableString($currentVersion),
            'from_version' => $this->nullableString($fromVersion),
            'update_cycle_id' => $this->nullableString($updateCycleId),
            'device_id' => $device?->getKey(),
            'user_id' => $resolvedUser?->getKey(),
            'attributed' => $device !== null || $resolvedUser !== null,
            'ip_address' => $normalizedIp,
            'ip_hash' => $normalizedIp ? hash_hmac('sha256', $normalizedIp, (string) config('app.key', '')) : null,
            'user_agent' => $this->nullableString($userAgent),
            'metadata' => collect($metadata)
                ->reject(static fn (mixed $value): bool => $value === null || $value === '')
                ->all(),
        ]);
    }

    private function normalizeIpAddress(?string $ipAddress): ?string
    {
        if (! is_string($ipAddress) || trim($ipAddress) === '') {
            return null;
        }

        $normalized = trim($ipAddress);

        if (! filter_var($normalized, FILTER_VALIDATE_IP)) {
            return null;
        }

        $packed = @inet_pton($normalized);

        if ($packed === false) {
            return $normalized;
        }

        return inet_ntop($packed) ?: $normalized;
    }

    private function nullableString(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
