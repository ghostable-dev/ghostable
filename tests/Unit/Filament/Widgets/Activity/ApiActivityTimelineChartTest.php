<?php

use App\Filament\Widgets\Activity\ApiActivityTimelineChart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('app.timezone', 'UTC');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function makeApiTimelineWidget(): ApiActivityTimelineChart
{
    return new class extends ApiActivityTimelineChart
    {
        /**
         * @return array<string, mixed>
         */
        public function exposedDataFor(string $range): array
        {
            $this->filter = $range;

            return $this->getData();
        }
    };
}

function logApiActivityWithSourceAt(string $source, Carbon $timestamp, string $description): void
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

it('builds lifetime buckets from api-scoped activity', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00', 'UTC'));

    logApiActivityWithSourceAt('cli', Carbon::parse('2026-01-15 09:00:00', 'UTC'), 'January CLI');
    logApiActivityWithSourceAt('desktop', Carbon::parse('2026-03-20 12:30:00', 'UTC'), 'March Desktop');
    logApiActivityWithSourceAt('web', Carbon::parse('2026-02-10 10:15:00', 'UTC'), 'Web login');

    $data = makeApiTimelineWidget()->exposedDataFor('lifetime');

    expect($data['labels'])->toBe([
        'Jan 2026',
        'Feb 2026',
        'Mar 2026',
    ])->and($data['datasets'][0]['data'])->toBe([
        1,
        0,
        1,
    ]);
});

it('shows fallback lifetime buckets when api activity does not exist', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-15 08:00:00', 'UTC'));

    $data = makeApiTimelineWidget()->exposedDataFor('lifetime');

    expect($data['labels'])->toHaveCount(12)
        ->and($data['labels'][0])->toBe('Jul 2025')
        ->and($data['labels'][11])->toBe('Jun 2026')
        ->and(array_unique($data['datasets'][0]['data']))->toBe([0]);
});
