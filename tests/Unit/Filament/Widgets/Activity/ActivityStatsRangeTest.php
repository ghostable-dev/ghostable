<?php

use App\Api\Usage\Models\ApiUsageDaily;
use App\Api\Usage\Models\ApiUsageHourly;
use App\Filament\Widgets\ApiActivitySourceStats;
use App\Filament\Widgets\ApiUsageStats;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('app.timezone', 'UTC');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function statsByLabel(array $stats): array
{
    return collect($stats)
        ->mapWithKeys(fn (Stat $stat) => [(string) $stat->getLabel() => $stat->getValue()])
        ->all();
}

function logApiActivityAt(string $source, Carbon $timestamp, string $description): void
{
    $activity = activity('variable')
        ->event('push')
        ->withProperties(['source' => $source])
        ->log($description);

    $activity->forceFill([
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ])->saveQuietly();
}

it('updates api activity source stats when range changes', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-20 10:30:00', 'UTC'));

    logApiActivityAt('cli', Carbon::parse('2026-03-20 09:00:00', 'UTC'), 'CLI today');
    logApiActivityAt('desktop', Carbon::parse('2026-03-20 09:15:00', 'UTC'), 'Desktop today');
    logApiActivityAt('cli', Carbon::parse('2026-03-01 12:00:00', 'UTC'), 'CLI earlier this month');

    $widget = new class extends ApiActivitySourceStats
    {
        public function exposedStats(): array
        {
            return $this->getStats();
        }
    };

    $monthStats = statsByLabel($widget->exposedStats());

    expect($monthStats['API Activity (This month)'])->toBe(3)
        ->and($monthStats['CLI (This month)'])->toBe(2)
        ->and($monthStats['Desktop (This month)'])->toBe(1);

    $widget->syncActivityRange('today');

    $todayStats = statsByLabel($widget->exposedStats());

    expect($todayStats['API Activity (Today)'])->toBe(2)
        ->and($todayStats['CLI (Today)'])->toBe(1)
        ->and($todayStats['Desktop (Today)'])->toBe(1);
});

it('updates api usage stats when range changes', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-20 10:30:00', 'UTC'));

    ApiUsageHourly::query()->create([
        'organization_id' => 'org-1',
        'token_id' => 'token-1',
        'method' => 'GET',
        'endpoint' => '/api/v2/projects',
        'hour' => Carbon::parse('2026-03-20 10:00:00', 'UTC'),
        'count' => 5,
    ]);

    ApiUsageDaily::query()->create([
        'organization_id' => 'org-1',
        'token_id' => 'token-1',
        'method' => 'GET',
        'endpoint' => '/api/v2/projects',
        'date' => Carbon::parse('2026-03-01', 'UTC'),
        'count' => 7,
    ]);

    ApiUsageDaily::query()->create([
        'organization_id' => 'org-1',
        'token_id' => 'token-2',
        'method' => 'POST',
        'endpoint' => '/api/v2/environments',
        'date' => Carbon::parse('2026-03-05', 'UTC'),
        'count' => 2,
    ]);

    $widget = new class extends ApiUsageStats
    {
        public function exposedStats(): array
        {
            return $this->getStats();
        }
    };

    $monthStats = statsByLabel($widget->exposedStats());

    expect($monthStats['API Calls (This month)'])->toBe(9)
        ->and($monthStats['Unique Endpoints'])->toBe(2)
        ->and($monthStats['Tokens Used'])->toBe(2);

    $widget->syncActivityRange('today');

    $todayStats = statsByLabel($widget->exposedStats());

    expect($todayStats['API Calls (Today)'])->toBe(5)
        ->and($todayStats['Unique Endpoints'])->toBe(1)
        ->and($todayStats['Tokens Used'])->toBe(1);
});
