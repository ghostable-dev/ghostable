<?php

use App\Core\Actions\DesktopUpdateTrackingSignature;
use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Models\DesktopUpdateEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function desktopTrackedPayload(array $overrides = []): array
{
    return app(DesktopUpdateTrackingSignature::class)->sign(array_merge([
        'channel' => 'stable',
        'release_version' => '3.0.0',
        'release_short_version' => '3.0.0',
        'from_version' => '2.9.0',
        'source' => 'sparkle',
        'target_url' => 'https://cdn.ghostable.dev/desktop/Ghostable-3.0.0.zip',
        'update_cycle_id' => '8f9b013d-5287-4c85-bd30-2db59bbad8e8',
    ], $overrides));
}

it('tracks unsigned latest download redirects against the current stable release', function (): void {
    config()->set('desktop-updates.channels.stable.download_url', 'https://cdn.ghostable.dev/desktop/Ghostable-1.2.3.dmg');
    config()->set('desktop-updates.channels.stable.version', '1.2.3');
    config()->set('desktop-updates.channels.stable.short_version', '1.2.3');

    $response = $this->get(route('desktop.download'));

    $response->assertRedirect('https://cdn.ghostable.dev/desktop/Ghostable-1.2.3.dmg');
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $event = DesktopUpdateEvent::query()->sole();

    expect($event->event_type)->toBe(DesktopUpdateEventType::DownloadRedirected)
        ->and($event->source)->toBe(DesktopUpdateSource::LatestDownload)
        ->and($event->channel)->toBe('stable')
        ->and($event->release_version)->toBe('1.2.3');
});

it('tracks signed sparkle downloads before redirecting to the release artifact', function (): void {
    $payload = desktopTrackedPayload();

    $response = $this->get(route('desktop.download', $payload));

    $response->assertRedirect('https://cdn.ghostable.dev/desktop/Ghostable-3.0.0.zip');
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $event = DesktopUpdateEvent::query()->sole();

    expect($event->event_type)->toBe(DesktopUpdateEventType::DownloadRedirected)
        ->and($event->source)->toBe(DesktopUpdateSource::Sparkle)
        ->and($event->channel)->toBe('stable')
        ->and($event->release_version)->toBe('3.0.0')
        ->and($event->current_version)->toBe('2.9.0')
        ->and($event->from_version)->toBe('2.9.0')
        ->and($event->update_cycle_id)->toBe('8f9b013d-5287-4c85-bd30-2db59bbad8e8');
});

it('rejects invalid signed download payloads', function (): void {
    $payload = desktopTrackedPayload();
    $payload['telemetry_signature'] = 'tampered-signature';

    $response = $this->get(route('desktop.download', $payload));

    $response->assertForbidden();

    expect(DesktopUpdateEvent::query()->count())->toBe(0);
});
