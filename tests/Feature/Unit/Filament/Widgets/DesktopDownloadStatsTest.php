<?php

use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Models\DesktopUpdateDailyRollup;
use App\Core\Models\DesktopUpdateEvent;
use App\Filament\Widgets\DesktopDownloadStats;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('app.timezone', 'UTC');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function desktopStatsByLabel(array $stats): array
{
    return collect($stats)
        ->mapWithKeys(fn (Stat $stat): array => [(string) $stat->getLabel() => $stat->getValue()])
        ->all();
}

function createDesktopRollup(string $date, DesktopUpdateEventType $eventType, int $count, bool $attributed = false): void
{
    DesktopUpdateDailyRollup::query()->create([
        'date' => $date,
        'event_type' => $eventType,
        'channel' => 'stable',
        'release_version' => '6.0.0',
        'release_short_version' => '6.0.0',
        'attributed' => $attributed,
        'total_events' => $count,
        'unique_ip_hashes' => $count,
        'unique_devices' => $attributed ? $count : 0,
        'unique_users' => $attributed ? $count : 0,
    ]);
}

function createDesktopRawEvent(string $timestamp, DesktopUpdateEventType $eventType, bool $attributed = false): void
{
    DesktopUpdateEvent::query()->create([
        'event_type' => $eventType,
        'source' => DesktopUpdateSource::Sparkle,
        'channel' => 'stable',
        'release_version' => '6.0.0',
        'release_short_version' => '6.0.0',
        'current_version' => '5.9.0',
        'from_version' => '5.9.0',
        'attributed' => $attributed,
        'ip_address' => '127.0.0.1',
        'ip_hash' => hash('sha256', $timestamp.$eventType->value),
        'user_agent' => 'GhostableDesktop/6.0',
        'metadata' => [],
        'created_at' => Carbon::parse($timestamp, 'UTC'),
        'updated_at' => Carbon::parse($timestamp, 'UTC'),
    ]);
}

it('combines rollups with raw events for the current day', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-20 10:30:00', 'UTC'));

    createDesktopRollup('2026-03-01', DesktopUpdateEventType::AppcastChecked, 10);
    createDesktopRollup('2026-03-02', DesktopUpdateEventType::DownloadRedirected, 4);
    createDesktopRollup('2026-03-03', DesktopUpdateEventType::UpdateInstalled, 1);
    createDesktopRollup('2026-03-04', DesktopUpdateEventType::UpdateInstalled, 2, attributed: true);

    createDesktopRawEvent('2026-03-20 09:00:00', DesktopUpdateEventType::AppcastChecked);
    createDesktopRawEvent('2026-03-20 09:30:00', DesktopUpdateEventType::AppcastChecked);
    createDesktopRawEvent('2026-03-20 10:00:00', DesktopUpdateEventType::AppcastChecked);
    createDesktopRawEvent('2026-03-20 09:15:00', DesktopUpdateEventType::DownloadRedirected);
    createDesktopRawEvent('2026-03-20 10:10:00', DesktopUpdateEventType::UpdateInstalled, attributed: true);

    $widget = new class extends DesktopDownloadStats
    {
        public function exposedStats(): array
        {
            return $this->getStats();
        }
    };

    $monthStats = desktopStatsByLabel($widget->exposedStats());

    expect($monthStats['Appcast Checks (This month)'])->toBe(13)
        ->and($monthStats['Downloads (This month)'])->toBe(5)
        ->and($monthStats['Successful Installs (This month)'])->toBe(4)
        ->and($monthStats['Attributed Installs (This month)'])->toBe(3);

    $widget->syncActivityRange('today');

    $todayStats = desktopStatsByLabel($widget->exposedStats());

    expect($todayStats['Appcast Checks (Today)'])->toBe(3)
        ->and($todayStats['Downloads (Today)'])->toBe(1)
        ->and($todayStats['Successful Installs (Today)'])->toBe(1)
        ->and($todayStats['Attributed Installs (Today)'])->toBe(1);
});
