<?php

use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Models\DesktopUpdateEvent;
use App\Filament\Resources\Core\DesktopUpdateEvent\DesktopUpdateEventResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('applies desktop update resource filters across event metadata', function (): void {
    $user = $this->createUser('Filter User', 'filters@example.com');
    $device = $this->createDevice($user, 'Filter Mac', 'macos', 'desktop');

    $matching = DesktopUpdateEvent::query()->create([
        'event_type' => DesktopUpdateEventType::UpdateInstalled,
        'source' => DesktopUpdateSource::Sparkle,
        'channel' => 'beta',
        'release_version' => '8.0.0',
        'release_short_version' => '8.0.0',
        'current_version' => '8.0.0',
        'from_version' => '7.9.0',
        'device_id' => $device->getKey(),
        'user_id' => $user->getKey(),
        'attributed' => true,
        'ip_address' => '127.0.0.1',
        'ip_hash' => hash('sha256', 'matching'),
        'user_agent' => 'GhostableDesktop/8.0',
        'metadata' => [],
        'created_at' => Carbon::parse('2026-03-19 12:00:00', 'UTC'),
        'updated_at' => Carbon::parse('2026-03-19 12:00:00', 'UTC'),
    ]);

    DesktopUpdateEvent::query()->create([
        'event_type' => DesktopUpdateEventType::UpdateFailed,
        'source' => DesktopUpdateSource::Sparkle,
        'channel' => 'beta',
        'release_version' => '8.0.0',
        'release_short_version' => '8.0.0',
        'current_version' => '7.9.0',
        'from_version' => '7.9.0',
        'attributed' => false,
        'ip_address' => '10.0.0.5',
        'ip_hash' => hash('sha256', 'other'),
        'user_agent' => 'GhostableDesktop/8.0',
        'metadata' => [],
        'created_at' => Carbon::parse('2026-03-19 12:30:00', 'UTC'),
        'updated_at' => Carbon::parse('2026-03-19 12:30:00', 'UTC'),
    ]);

    $ids = DesktopUpdateEventResource::applyFiltersTo(DesktopUpdateEvent::query(), [
        'event_type' => DesktopUpdateEventType::UpdateInstalled->value,
        'source' => DesktopUpdateSource::Sparkle->value,
        'channel' => 'beta',
        'release_version' => '8.0.0',
        'user_id' => $user->getKey(),
        'device_id' => $device->getKey(),
        'attributed' => true,
        'from' => '2026-03-19',
        'until' => '2026-03-19',
    ])->pluck('id')->all();

    expect($ids)->toBe([$matching->id]);
});

it('can isolate anonymous latest download records', function (): void {
    $anonymous = DesktopUpdateEvent::query()->create([
        'event_type' => DesktopUpdateEventType::DownloadRedirected,
        'source' => DesktopUpdateSource::LatestDownload,
        'channel' => 'stable',
        'release_version' => '8.1.0',
        'release_short_version' => '8.1.0',
        'current_version' => null,
        'from_version' => null,
        'attributed' => false,
        'ip_address' => '127.0.0.1',
        'ip_hash' => hash('sha256', 'anonymous'),
        'user_agent' => 'Mozilla/5.0',
        'metadata' => [],
        'created_at' => Carbon::parse('2026-03-20 09:00:00', 'UTC'),
        'updated_at' => Carbon::parse('2026-03-20 09:00:00', 'UTC'),
    ]);

    DesktopUpdateEvent::query()->create([
        'event_type' => DesktopUpdateEventType::DownloadRedirected,
        'source' => DesktopUpdateSource::Sparkle,
        'channel' => 'stable',
        'release_version' => '8.1.0',
        'release_short_version' => '8.1.0',
        'current_version' => '8.0.0',
        'from_version' => '8.0.0',
        'attributed' => true,
        'ip_address' => '10.0.0.8',
        'ip_hash' => hash('sha256', 'sparkle'),
        'user_agent' => 'GhostableDesktop/8.1.0',
        'metadata' => [],
        'created_at' => Carbon::parse('2026-03-20 10:00:00', 'UTC'),
        'updated_at' => Carbon::parse('2026-03-20 10:00:00', 'UTC'),
    ]);

    $ids = DesktopUpdateEventResource::applyFiltersTo(DesktopUpdateEvent::query(), [
        'source' => DesktopUpdateSource::LatestDownload->value,
        'attributed' => false,
    ])->pluck('id')->all();

    expect($ids)->toBe([$anonymous->id]);
});
