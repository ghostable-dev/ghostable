<?php

use App\Core\Models\Activity;
use App\Filament\Widgets\Activity\ActivityTimelineChart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('app.timezone', 'UTC');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function makeWidget(): ActivityTimelineChart
{
    return new class extends ActivityTimelineChart
    {
        protected ?string $heading = 'Test Activity Trend';

        protected function getActivityQuery(): Builder
        {
            return Activity::query()->where('log_name', 'variable');
        }

        public function exposedFilters(): ?array
        {
            return $this->getFilters();
        }

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

function logVariableActivityAt(Carbon $timestamp, string $description): void
{
    $activity = activity('variable')
        ->event('push')
        ->withProperties(['source' => 'cli'])
        ->log($description);

    $activity->forceFill([
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ])->saveQuietly();
}

it('provides fathom-style range filters', function (): void {
    $filters = makeWidget()->exposedFilters();

    expect($filters)->toBe([
        'today' => 'Today',
        'this_week' => 'This week',
        'this_month' => 'This month',
        'lifetime' => 'Lifetime',
    ]);
});

it('returns an empty-state dataset when no activity exists', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-04 15:30:00', 'UTC'));

    $data = makeWidget()->exposedDataFor('today');

    expect($data['labels'])->toHaveCount(16)
        ->and(array_unique($data['datasets'][0]['data']))->toBe([0]);
});

it('builds lifetime monthly buckets with zero-filled months', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00', 'UTC'));

    logVariableActivityAt(Carbon::parse('2026-01-15 09:00:00', 'UTC'), 'January activity');
    logVariableActivityAt(Carbon::parse('2026-03-20 12:30:00', 'UTC'), 'March activity');

    $data = makeWidget()->exposedDataFor('lifetime');

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
