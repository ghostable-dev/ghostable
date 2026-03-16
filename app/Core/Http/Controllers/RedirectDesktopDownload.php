<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use App\Core\Actions\DesktopUpdateTrackingSignature;
use App\Core\Actions\RecordDesktopUpdateEvent;
use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RedirectDesktopDownload
{
    public function __invoke(
        Request $request,
        DesktopUpdateTrackingSignature $desktopUpdateTrackingSignature,
        RecordDesktopUpdateEvent $recordDesktopUpdateEvent,
    ): RedirectResponse {
        if ($desktopUpdateTrackingSignature->hasTrackedPayload($request)) {
            $trackingPayload = $desktopUpdateTrackingSignature->extractFromRequest($request);

            abort_unless($desktopUpdateTrackingSignature->isValid($trackingPayload), 403, 'Invalid desktop download signature.');

            $targetUrl = trim((string) ($trackingPayload['target_url'] ?? ''));

            if ($targetUrl === '' || ! filter_var($targetUrl, FILTER_VALIDATE_URL)) {
                abort(404, 'Desktop download URL is not configured.');
            }

            $source = DesktopUpdateSource::tryFrom((string) ($trackingPayload['source'] ?? DesktopUpdateSource::Sparkle->value))
                ?? DesktopUpdateSource::Sparkle;
            $fromVersion = $this->nullableString($trackingPayload['from_version'] ?? null);

            $recordDesktopUpdateEvent->handle(
                eventType: DesktopUpdateEventType::DownloadRedirected,
                source: $source,
                channel: (string) ($trackingPayload['channel'] ?? 'stable'),
                releaseVersion: $this->nullableString($trackingPayload['release_version'] ?? null),
                releaseShortVersion: $this->nullableString($trackingPayload['release_short_version'] ?? null),
                currentVersion: $fromVersion,
                fromVersion: $fromVersion,
                updateCycleId: $this->nullableString($trackingPayload['update_cycle_id'] ?? null),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            return redirect()->away($targetUrl);
        }

        $downloadUrl = trim((string) config('desktop-updates.channels.stable.download_url'));

        if ($downloadUrl === '') {
            abort(404, 'Desktop download URL is not configured.');
        }

        $recordDesktopUpdateEvent->handle(
            eventType: DesktopUpdateEventType::DownloadRedirected,
            source: DesktopUpdateSource::LatestDownload,
            channel: 'stable',
            releaseVersion: $this->nullableString((string) config('desktop-updates.channels.stable.version')),
            releaseShortVersion: $this->nullableString((string) config('desktop-updates.channels.stable.short_version')),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()->away($downloadUrl);
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
