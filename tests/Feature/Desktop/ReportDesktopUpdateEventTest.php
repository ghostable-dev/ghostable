<?php

use App\Core\Actions\DesktopUpdateTrackingSignature;
use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Models\DesktopUpdateEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function desktopTelemetryPayload(array $overrides = []): array
{
    return app(DesktopUpdateTrackingSignature::class)->sign(array_merge([
        'channel' => 'stable',
        'release_version' => '4.1.0',
        'release_short_version' => '4.1.0',
        'from_version' => '4.0.5',
        'source' => 'sparkle',
        'target_url' => 'https://cdn.ghostable.dev/desktop/Ghostable-4.1.0.zip',
        'update_cycle_id' => 'c950d2b8-9836-41c8-9f78-4dfd819eb7b8',
    ], $overrides));
}

it('records attributed install telemetry from the desktop client', function (): void {
    $user = $this->createUser('Desktop User', 'desktop@example.com');
    $device = $this->createDevice($user, 'Riley Mac', 'macos', 'desktop');

    $response = $this->postJson(route('desktop.update-events', desktopTelemetryPayload()), [
        'event_type' => 'update_installed',
        'device_id' => $device->getKey(),
        'metadata' => [
            'phase' => 'install',
        ],
    ]);

    $response->assertAccepted();

    $event = DesktopUpdateEvent::query()->sole();

    expect($event->event_type)->toBe(DesktopUpdateEventType::UpdateInstalled)
        ->and($event->source)->toBe(DesktopUpdateSource::Sparkle)
        ->and($event->release_version)->toBe('4.1.0')
        ->and($event->current_version)->toBe('4.1.0')
        ->and($event->from_version)->toBe('4.0.5')
        ->and($event->device_id)->toBe($device->getKey())
        ->and($event->user_id)->toBe($user->getKey())
        ->and($event->attributed)->toBeTrue()
        ->and(data_get($event->metadata, 'phase'))->toBe('install');
});

it('records anonymous failure telemetry including error details', function (): void {
    $response = $this->postJson(route('desktop.update-events', desktopTelemetryPayload()), [
        'event_type' => 'update_failed',
        'current_version' => '4.0.5',
        'metadata' => [
            'phase' => 'download',
        ],
        'error' => [
            'code' => 'network_unavailable',
            'message' => 'The update download failed.',
        ],
    ]);

    $response->assertAccepted();

    $event = DesktopUpdateEvent::query()->sole();

    expect($event->event_type)->toBe(DesktopUpdateEventType::UpdateFailed)
        ->and($event->source)->toBe(DesktopUpdateSource::Sparkle)
        ->and($event->device_id)->toBeNull()
        ->and($event->user_id)->toBeNull()
        ->and($event->attributed)->toBeFalse()
        ->and(data_get($event->metadata, 'phase'))->toBe('download')
        ->and(data_get($event->metadata, 'error.code'))->toBe('network_unavailable');
});

it('rejects update telemetry with invalid signatures', function (): void {
    $payload = desktopTelemetryPayload();
    $payload['telemetry_signature'] = 'invalid';

    $response = $this->postJson(route('desktop.update-events', $payload), [
        'event_type' => 'update_downloaded',
    ]);

    $response->assertForbidden();

    expect(DesktopUpdateEvent::query()->count())->toBe(0);
});
