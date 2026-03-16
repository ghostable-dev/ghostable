<?php

use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Models\DesktopUpdateDailyRollup;
use App\Core\Models\DesktopUpdateEvent;
use App\Filament\Widgets\Activity\DesktopDownloadTimelineChart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('app.timezone', 'UTC');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function makeDesktopTimelineWidget(): DesktopDownloadTimelineChart
{
    return new class extends DesktopDownloadTimelineChart
    {
        public function exposedDataFor(string $range): array
        {
            $this->filter = $range;

            return $this->getData();
        }
    };
}

function createDesktopTimelineRollup(string $date, DesktopUpdateEventType $eventType, int $count): void
{
    DesktopUpdateDailyRollup::query()->create([
        'date' => $date,
        'event_type' => $eventType,
        'channel' => 'stable',
        'release_version' => '7.0.0',
        'release_short_version' => '7.0.0',
        'attributed' => false,
        'total_events' => $count,
        'unique_ip_hashes' => $count,
        'unique_devices' => 0,
        'unique_users' => 0,
    ]);
}

function createDesktopTimelineRawEvent(string $timestamp, DesktopUpdateEventType $eventType): void
{
    DesktopUpdateEvent::query()->create([
        'event_type' => $eventType,
        'source' => DesktopUpdateSource::Sparkle,
        'channel' => 'stable',
        'release_version' => '7.0.0',
        'release_short_version' => '7.0.0',
        'current_version' => '6.9.0',
        'from_version' => '6.9.0',
        'attributed' => false,
        'ip_address' => '127.0.0.1',
        'ip_hash' => hash('sha256', $timestamp.$eventType->value),
        'user_agent' => 'GhostableDesktop/7.0',
        'metadata' => [],
        'created_at' => Carbon::parse($timestamp, 'UTC'),
        'updated_at' => Carbon::parse($timestamp, 'UTC'),
    ]);
}

it('builds lifetime series from rollups and raw events', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00', 'UTC'));

    createDesktopTimelineRollup('2026-01-15', DesktopUpdateEventType::DownloadRedirected, 4);
    createDesktopTimelineRollup('2026-01-15', DesktopUpdateEventType::UpdateInstalled, 1);
    createDesktopTimelineRollup('2026-03-20', DesktopUpdateEventType::DownloadRedirected, 2);
    createDesktopTimelineRollup('2026-03-20', DesktopUpdateEventType::UpdateInstalled, 1);

    createDesktopTimelineRawEvent('2026-03-31 09:15:00', DesktopUpdateEventType::DownloadRedirected);
    createDesktopTimelineRawEvent('2026-03-31 09:30:00', DesktopUpdateEventType::UpdateInstalled);
    createDesktopTimelineRawEvent('2026-03-31 10:00:00', DesktopUpdateEventType::UpdateInstalled);

    $data = makeDesktopTimelineWidget()->exposedDataFor('lifetime');

    expect($data['labels'])->toBe([
        'Jan 2026',
        'Feb 2026',
        'Mar 2026',
    ])->and($data['datasets'][0]['data'])->toBe([
        4,
        0,
        3,
    ])->and($data['datasets'][1]['data'])->toBe([
        1,
        0,
        3,
    ]);
});
