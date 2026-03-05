<?php

namespace App\Filament\Widgets\Activity;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

abstract class ActivityTimelineChart extends ChartWidget
{
    public const RANGE_CHANGED_EVENT = 'activity-range-updated';

    /**
     * @var array<string, string>
     */
    public const RANGE_OPTIONS = [
        'today' => 'Today',
        'this_week' => 'This week',
        'this_month' => 'This month',
        'lifetime' => 'Lifetime',
    ];

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '10s';

    protected ?string $maxHeight = '260px';

    public ?string $filter = 'this_month';

    abstract protected function getActivityQuery(): Builder;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return self::RANGE_OPTIONS;
    }

    public function mount(): void
    {
        $this->filter = $this->normalizeRange($this->filter);

        parent::mount();
    }

    public function updatedFilter(?string $range): void
    {
        $this->filter = $this->normalizeRange($range);

        $this->dispatch(self::RANGE_CHANGED_EVENT, range: $this->filter);
    }

    protected function getData(): array
    {
        $timezone = timezone();
        $now = now()->timezone($timezone);

        $labels = [];
        $data = [];

        foreach ($this->resolveBuckets($now) as $bucket) {
            $labels[] = $bucket['label'];
            $data[] = (clone $this->getActivityQuery())
                ->whereBetween('created_at', [
                    $bucket['start']->copy()->timezone('UTC'),
                    $bucket['end']->copy()->timezone('UTC'),
                ])
                ->count();
        }

        if ($labels === []) {
            $labels = ['No data'];
            $data = [0];
        }

        return [
            'datasets' => [[
                'label' => 'Events',
                'data' => $data,
                'fill' => true,
                'tension' => 0.35,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array{start: Carbon, end: Carbon, label: string}>
     */
    private function resolveBuckets(Carbon $now): array
    {
        return match ($this->filter ?? 'this_month') {
            'this_week' => $this->dayBuckets($now->copy()->startOfWeek(), $now, 'D'),
            'this_month' => $this->dayBuckets($now->copy()->startOfMonth(), $now, 'M j'),
            'lifetime' => $this->lifetimeBuckets($now),
            default => $this->hourBuckets($now->copy()->startOfDay(), $now),
        };
    }

    protected function normalizeRange(?string $range): string
    {
        if (is_string($range) && array_key_exists($range, self::RANGE_OPTIONS)) {
            return $range;
        }

        return 'this_month';
    }

    /**
     * @return list<array{start: Carbon, end: Carbon, label: string}>
     */
    private function hourBuckets(Carbon $start, Carbon $end): array
    {
        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $bucketEnd = $cursor->copy()->endOfHour();
            if ($bucketEnd->greaterThan($end)) {
                $bucketEnd = $end->copy();
            }

            $buckets[] = [
                'start' => $cursor->copy(),
                'end' => $bucketEnd,
                'label' => $cursor->format('g A'),
            ];

            $cursor->addHour();
        }

        return $buckets;
    }

    /**
     * @return list<array{start: Carbon, end: Carbon, label: string}>
     */
    private function dayBuckets(Carbon $start, Carbon $end, string $labelFormat): array
    {
        $buckets = [];
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $bucketEnd = $cursor->copy()->endOfDay();
            if ($bucketEnd->greaterThan($end)) {
                $bucketEnd = $end->copy();
            }

            $buckets[] = [
                'start' => $cursor->copy(),
                'end' => $bucketEnd,
                'label' => $cursor->format($labelFormat),
            ];

            $cursor->addDay();
        }

        return $buckets;
    }

    /**
     * @return list<array{start: Carbon, end: Carbon, label: string}>
     */
    private function lifetimeBuckets(Carbon $now): array
    {
        $firstActivityAt = (clone $this->getActivityQuery())
            ->orderBy('created_at')
            ->value('created_at');

        if (! is_string($firstActivityAt) && ! $firstActivityAt instanceof \DateTimeInterface) {
            return $this->recentMonthBuckets($now, 12);
        }

        $cursor = Carbon::parse((string) $firstActivityAt)->timezone(timezone())->startOfMonth();
        $end = $now->copy();
        $buckets = [];

        while ($cursor->lessThanOrEqualTo($end)) {
            $bucketEnd = $cursor->copy()->endOfMonth();
            if ($bucketEnd->greaterThan($end)) {
                $bucketEnd = $end->copy();
            }

            $buckets[] = [
                'start' => $cursor->copy(),
                'end' => $bucketEnd,
                'label' => $cursor->format('M Y'),
            ];

            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        return $buckets;
    }

    /**
     * @return list<array{start: Carbon, end: Carbon, label: string}>
     */
    private function recentMonthBuckets(Carbon $now, int $months): array
    {
        $months = max(1, $months);

        $start = $now->copy()
            ->startOfMonth()
            ->subMonthsNoOverflow($months - 1);

        return $this->monthBuckets($start, $now);
    }

    /**
     * @return list<array{start: Carbon, end: Carbon, label: string}>
     */
    private function monthBuckets(Carbon $start, Carbon $end): array
    {
        $buckets = [];
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            $bucketEnd = $cursor->copy()->endOfMonth();
            if ($bucketEnd->greaterThan($end)) {
                $bucketEnd = $end->copy();
            }

            $buckets[] = [
                'start' => $cursor->copy(),
                'end' => $bucketEnd,
                'label' => $cursor->format('M Y'),
            ];

            $cursor->addMonthNoOverflow()->startOfMonth();
        }

        return $buckets;
    }
}
