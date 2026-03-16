<?php

use App\Core\Actions\DesktopUpdateTrackingSignature;
use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Models\DesktopUpdateEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function configureDesktopReleaseChannel(string $channel, array $overrides = []): void
{
    config()->set("desktop-updates.channels.{$channel}", array_merge([
        'version' => '2.3.4',
        'short_version' => '2.3.4',
        'download_url' => "https://cdn.ghostable.dev/desktop/Ghostable-{$channel}-2.3.4.zip",
        'ed_signature' => 'signature-value',
        'length' => 4096,
        'pub_date' => 'Mon, 16 Mar 2026 10:00:00 +0000',
        'release_notes_url' => 'https://ghostable.dev/releases/2.3.4',
        'minimum_system_version' => '13.0.0',
        'title' => 'Ghostable 2.3.4',
        'description' => 'Ghostable desktop release.',
    ], $overrides));
}

it('logs appcast checks and emits tracked enclosure urls with device attribution', function (): void {
    configureDesktopReleaseChannel('beta');

    $user = $this->createUser('Appcast User', 'appcast@example.com');
    $device = $this->createDevice($user, 'Riley Mac', 'macos', 'desktop');

    $response = $this->get(route('desktop.appcast', [
        'channel' => 'beta',
        'device_id' => $device->getKey(),
        'current_version' => '2.3.3',
    ]));

    $response->assertSuccessful();

    $xml = simplexml_load_string($response->getContent());

    expect($xml)->not->toBeFalse();

    $enclosureUrl = (string) $xml->channel->item->enclosure['url'];
    parse_str((string) parse_url($enclosureUrl, PHP_URL_QUERY), $query);

    expect($query['channel'])->toBe('beta')
        ->and($query['release_version'])->toBe('2.3.4')
        ->and($query['release_short_version'])->toBe('2.3.4')
        ->and($query['from_version'])->toBe('2.3.3')
        ->and($query['source'])->toBe('sparkle')
        ->and(app(DesktopUpdateTrackingSignature::class)->isValid($query))->toBeTrue();

    $event = DesktopUpdateEvent::query()->sole();

    expect($event->event_type)->toBe(DesktopUpdateEventType::AppcastChecked)
        ->and($event->source)->toBe(DesktopUpdateSource::Sparkle)
        ->and($event->channel)->toBe('beta')
        ->and($event->release_version)->toBe('2.3.4')
        ->and($event->current_version)->toBe('2.3.3')
        ->and($event->device_id)->toBe($device->getKey())
        ->and($event->user_id)->toBe($user->getKey());
});

it('returns an empty feed item list when the release is incomplete', function (): void {
    configureDesktopReleaseChannel('stable', ['download_url' => null]);

    $response = $this->get(route('desktop.appcast'));

    $response->assertSuccessful();

    $xml = simplexml_load_string($response->getContent());

    expect($xml)->not->toBeFalse()
        ->and(isset($xml->channel->item))->toBeFalse()
        ->and(DesktopUpdateEvent::query()->count())->toBe(1);
});
