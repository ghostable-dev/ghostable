<?php

declare(strict_types=1);

namespace App\Core\Http\Controllers;

use App\Core\Actions\DesktopUpdateTrackingSignature;
use App\Core\Actions\RecordDesktopUpdateEvent;
use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Crypto\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class GetDesktopAppcast extends Controller
{
    public function __invoke(
        Request $request,
        DesktopUpdateTrackingSignature $desktopUpdateTrackingSignature,
        RecordDesktopUpdateEvent $recordDesktopUpdateEvent,
    ): Response {
        $channel = $this->resolveChannel($request);
        $release = config("desktop-updates.channels.{$channel}", []);
        $device = $this->resolveDevice($request->query('device_id'));
        $currentVersion = $this->nullableString($request->query('current_version'));

        $recordDesktopUpdateEvent->handle(
            eventType: DesktopUpdateEventType::AppcastChecked,
            source: DesktopUpdateSource::Sparkle,
            channel: $channel,
            releaseVersion: $this->nullableString((string) Arr::get($release, 'version')),
            releaseShortVersion: $this->nullableString((string) Arr::get($release, 'short_version')),
            currentVersion: $currentVersion,
            fromVersion: $currentVersion,
            device: $device,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            metadata: collect([
                'reported_device_id' => $this->nullableString($request->query('device_id')),
                'request_query' => array_filter([
                    'channel' => $request->query('channel'),
                    'current_version' => $currentVersion,
                ]),
            ])->filter(static fn (mixed $value): bool => $value !== null && $value !== [])->all(),
        );

        $xml = [
            '<?xml version="1.0" encoding="utf-8"?>',
            '<rss version="2.0" xmlns:sparkle="http://www.andymatuschak.org/xml-namespaces/sparkle" xmlns:dc="http://purl.org/dc/elements/1.1/">',
            '  <channel>',
            '    <title>'.$this->escape((string) config('desktop-updates.title', config('app.name', 'Ghostable').' Desktop Updates')).'</title>',
            '    <link>'.$this->escape((string) config('desktop-updates.link', rtrim((string) config('app.url', ''), '/'))).'</link>',
            '    <description>'.$this->escape((string) config('desktop-updates.description', 'Release feed for the Ghostable desktop app.')).'</description>',
            '    <language>'.$this->escape((string) config('desktop-updates.language', 'en-US')).'</language>',
            '    <atom:link xmlns:atom="http://www.w3.org/2005/Atom" rel="self" type="application/rss+xml" href="'.$this->escape($request->fullUrl()).'" />',
        ];

        if ($this->hasRelease($release)) {
            $xml = [...$xml, ...$this->buildItemXml(
                channel: $channel,
                release: $release,
                currentVersion: $currentVersion,
                desktopUpdateTrackingSignature: $desktopUpdateTrackingSignature,
            )];
        }

        $xml[] = '  </channel>';
        $xml[] = '</rss>';

        return response(implode("\n", $xml), 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    private function resolveChannel(Request $request): string
    {
        $requested = strtolower((string) $request->query('channel', 'stable'));

        return in_array($requested, ['stable', 'beta'], true)
            ? $requested
            : 'stable';
    }

    /**
     * @param  array<string, mixed>  $release
     * @return array<int, string>
     */
    private function buildItemXml(
        string $channel,
        array $release,
        ?string $currentVersion,
        DesktopUpdateTrackingSignature $desktopUpdateTrackingSignature,
    ): array {
        $shortVersion = (string) Arr::get($release, 'short_version');
        $title = (string) (Arr::get($release, 'title') ?: 'Ghostable '.$shortVersion);
        $description = (string) Arr::get($release, 'description', '');
        $releaseNotesUrl = (string) Arr::get($release, 'release_notes_url', '');
        $minimumSystemVersion = (string) Arr::get($release, 'minimum_system_version', '');
        $version = (string) Arr::get($release, 'version');
        $downloadUrl = (string) Arr::get($release, 'download_url');
        $edSignature = (string) Arr::get($release, 'ed_signature');
        $length = max(0, (int) Arr::get($release, 'length', 0));
        $pubDate = (string) Arr::get($release, 'pub_date', now()->toRfc2822String());
        $trackedDownloadUrl = route('desktop.download', $desktopUpdateTrackingSignature->sign([
            'channel' => $channel,
            'release_version' => $version,
            'release_short_version' => $shortVersion,
            'from_version' => $currentVersion,
            'source' => DesktopUpdateSource::Sparkle->value,
            'target_url' => $downloadUrl,
            'update_cycle_id' => (string) Str::uuid(),
        ]));

        $enclosureAttributes = [
            'url="'.$this->escape($trackedDownloadUrl).'"',
            'sparkle:version="'.$this->escape($version).'"',
            'sparkle:shortVersionString="'.$this->escape($shortVersion).'"',
            'sparkle:edSignature="'.$this->escape($edSignature).'"',
            'length="'.$length.'"',
            'type="application/octet-stream"',
        ];

        if ($minimumSystemVersion !== '') {
            $enclosureAttributes[] = 'sparkle:minimumSystemVersion="'.$this->escape($minimumSystemVersion).'"';
        }

        $xml = [
            '    <item>',
            '      <title>'.$this->escape($title).'</title>',
            '      <pubDate>'.$this->escape($pubDate).'</pubDate>',
        ];

        if ($description !== '') {
            $xml[] = '      <description>'.$this->escape($description).'</description>';
        }

        if ($releaseNotesUrl !== '') {
            $xml[] = '      <sparkle:releaseNotesLink>'.$this->escape($releaseNotesUrl).'</sparkle:releaseNotesLink>';
        }

        if ($channel !== 'stable') {
            $xml[] = '      <sparkle:channel>'.$this->escape($channel).'</sparkle:channel>';
        }

        $xml[] = '      <enclosure '.implode(' ', $enclosureAttributes).' />';
        $xml[] = '    </item>';

        return $xml;
    }

    /**
     * @param  array<string, mixed>  $release
     */
    private function hasRelease(array $release): bool
    {
        foreach (['version', 'short_version', 'download_url', 'ed_signature'] as $key) {
            if (trim((string) Arr::get($release, $key, '')) === '') {
                return false;
            }
        }

        return true;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function resolveDevice(mixed $deviceId): ?Device
    {
        if (! is_scalar($deviceId) || trim((string) $deviceId) === '') {
            return null;
        }

        return Device::query()->find((string) $deviceId);
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
