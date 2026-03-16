<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use App\Core\Actions\DesktopUpdateTrackingSignature;
use App\Core\Actions\RecordDesktopUpdateEvent;
use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Http\Requests\ReportDesktopUpdateEventRequest;
use App\Crypto\Models\Device;
use Illuminate\Http\JsonResponse;

final class ReportDesktopUpdateEvent extends Controller
{
    public function __invoke(
        ReportDesktopUpdateEventRequest $request,
        DesktopUpdateTrackingSignature $desktopUpdateTrackingSignature,
        RecordDesktopUpdateEvent $recordDesktopUpdateEvent,
    ): JsonResponse {
        $trackingPayload = $desktopUpdateTrackingSignature->extractFromRequest($request);

        abort_unless($desktopUpdateTrackingSignature->isValid($trackingPayload), 403, 'Invalid desktop telemetry signature.');

        $validated = $request->validated();
        $device = $this->resolveDevice($validated['device_id'] ?? null);
        $eventType = DesktopUpdateEventType::from((string) $validated['event_type']);
        $source = DesktopUpdateSource::tryFrom((string) ($trackingPayload['source'] ?? DesktopUpdateSource::Sparkle->value))
            ?? DesktopUpdateSource::Sparkle;
        $fromVersion = $this->nullableString($validated['from_version'] ?? null)
            ?? $this->nullableString($trackingPayload['from_version'] ?? null);
        $currentVersion = $this->nullableString($validated['current_version'] ?? null)
            ?? ($eventType === DesktopUpdateEventType::UpdateInstalled
                ? $this->nullableString($trackingPayload['release_version'] ?? null)
                : $fromVersion);
        $metadata = $validated['metadata'] ?? [];

        if (! empty($validated['error'])) {
            $metadata['error'] = $validated['error'];
        }

        $recordDesktopUpdateEvent->handle(
            eventType: $eventType,
            source: $source,
            channel: (string) ($trackingPayload['channel'] ?? 'stable'),
            releaseVersion: $this->nullableString($trackingPayload['release_version'] ?? null),
            releaseShortVersion: $this->nullableString($trackingPayload['release_short_version'] ?? null),
            currentVersion: $currentVersion,
            fromVersion: $fromVersion,
            updateCycleId: $this->nullableString($trackingPayload['update_cycle_id'] ?? null),
            device: $device,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            metadata: $metadata,
        );

        return response()->json([
            'status' => 'accepted',
        ], 202);
    }

    private function resolveDevice(?string $deviceId): ?Device
    {
        if (! is_string($deviceId) || trim($deviceId) === '') {
            return null;
        }

        return Device::query()->find($deviceId);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
