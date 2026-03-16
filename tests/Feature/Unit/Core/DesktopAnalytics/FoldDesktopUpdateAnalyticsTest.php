<?php

use App\Core\Actions\FoldDesktopUpdateAnalytics;
use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Models\DesktopUpdateDailyRollup;
use App\Core\Models\DesktopUpdateEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function (): void {
    Carbon::setTestNow();
});

function createFoldEvent(array $attributes): DesktopUpdateEvent
{
    return DesktopUpdateEvent::query()->create(array_merge([
        'event_type' => DesktopUpdateEventType::DownloadRedirected,
        'source' => DesktopUpdateSource::Sparkle,
        'channel' => 'stable',
        'release_version' => '5.0.0',
        'release_short_version' => '5.0.0',
        'current_version' => '4.9.0',
        'from_version' => '4.9.0',
        'user_agent' => 'GhostableDesktop/5.0',
        'metadata' => [],
        'created_at' => Carbon::parse('2026-03-19 10:00:00', 'UTC'),
        'updated_at' => Carbon::parse('2026-03-19 10:00:00', 'UTC'),
    ], $attributes));
}

it('rebuilds daily rollups idempotently for pending event dates', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-20 10:30:00', 'UTC'));

    $user = $this->createUser('Fold User', 'fold@example.com');
    $device = $this->createDevice($user, 'Fold Mac', 'macos', 'desktop');

    createFoldEvent([
        'ip_address' => '127.0.0.1',
        'ip_hash' => hash('sha256', '127.0.0.1'),
    ]);
    createFoldEvent([
        'ip_address' => '127.0.0.1',
        'ip_hash' => hash('sha256', '127.0.0.1'),
        'created_at' => Carbon::parse('2026-03-19 11:00:00', 'UTC'),
        'updated_at' => Carbon::parse('2026-03-19 11:00:00', 'UTC'),
    ]);
    createFoldEvent([
        'ip_address' => '10.0.0.8',
        'ip_hash' => hash('sha256', '10.0.0.8'),
        'created_at' => Carbon::parse('2026-03-19 12:00:00', 'UTC'),
        'updated_at' => Carbon::parse('2026-03-19 12:00:00', 'UTC'),
    ]);
    createFoldEvent([
        'event_type' => DesktopUpdateEventType::UpdateInstalled,
        'device_id' => $device->getKey(),
        'user_id' => $user->getKey(),
        'attributed' => true,
        'ip_address' => '192.168.1.5',
        'ip_hash' => hash('sha256', '192.168.1.5'),
    ]);
    createFoldEvent([
        'event_type' => DesktopUpdateEventType::UpdateInstalled,
        'device_id' => $device->getKey(),
        'user_id' => $user->getKey(),
        'attributed' => true,
        'ip_address' => '192.168.1.5',
        'ip_hash' => hash('sha256', '192.168.1.5'),
        'created_at' => Carbon::parse('2026-03-19 13:00:00', 'UTC'),
        'updated_at' => Carbon::parse('2026-03-19 13:00:00', 'UTC'),
    ]);

    $processed = app(FoldDesktopUpdateAnalytics::class)->handle();

    expect($processed)->toBe(5)
        ->and(DesktopUpdateEvent::query()->whereNull('rolled_up_at')->count())->toBe(0);

    $downloadRollup = DesktopUpdateDailyRollup::query()
        ->where('date', '2026-03-19')
        ->where('event_type', DesktopUpdateEventType::DownloadRedirected->value)
        ->where('attributed', false)
        ->sole();

    $installRollup = DesktopUpdateDailyRollup::query()
        ->where('date', '2026-03-19')
        ->where('event_type', DesktopUpdateEventType::UpdateInstalled->value)
        ->where('attributed', true)
        ->sole();

    expect($downloadRollup->total_events)->toBe(3)
        ->and($downloadRollup->unique_ip_hashes)->toBe(2)
        ->and($installRollup->total_events)->toBe(2)
        ->and($installRollup->unique_ip_hashes)->toBe(1)
        ->and($installRollup->unique_devices)->toBe(1)
        ->and($installRollup->unique_users)->toBe(1);

    expect(app(FoldDesktopUpdateAnalytics::class)->handle())->toBe(0);

    createFoldEvent([
        'ip_address' => '10.0.0.9',
        'ip_hash' => hash('sha256', '10.0.0.9'),
        'created_at' => Carbon::parse('2026-03-19 14:00:00', 'UTC'),
        'updated_at' => Carbon::parse('2026-03-19 14:00:00', 'UTC'),
    ]);

    expect(app(FoldDesktopUpdateAnalytics::class)->handle())->toBe(1);

    $downloadRollup = DesktopUpdateDailyRollup::query()
        ->whereDate('date', '2026-03-19')
        ->where('event_type', DesktopUpdateEventType::DownloadRedirected->value)
        ->where('attributed', false)
        ->firstOrFail();

    expect($downloadRollup->total_events)->toBe(4)
        ->and($downloadRollup->unique_ip_hashes)->toBe(3);
});
