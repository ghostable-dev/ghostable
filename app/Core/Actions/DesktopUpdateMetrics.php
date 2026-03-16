<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Models\DesktopUpdateDailyRollup;
use App\Core\Models\DesktopUpdateEvent;
use App\Filament\Widgets\Activity\ActivityTimelineChart;
use Illuminate\Support\Carbon;

final class DesktopUpdateMetrics
{
    public function count(DesktopUpdateEventType $eventType, string $range, bool $attributedOnly = false): int
    {
        [$start, $end] = $this->rangeWindow($range);

        return $this->countBetween($eventType, $start, $end, $attributedOnly);
    }

    /**
     * @param  list<DesktopUpdateEventType>  $eventTypes
     * @return array{labels: list<string>, datasets: array<string, list<int>>}
     */
    public function series(string $range, array $eventTypes): array
    {
        $buckets = $this->resolveBuckets($range, now()->timezone(timezone()));
        $datasets = [];

        foreach ($eventTypes as $eventType) {
            $datasets[$eventType->value] = [];

            foreach ($buckets as $bucket) {
                $datasets[$eventType->value][] = $this->countBetween(
                    $eventType,
                    $bucket['start'],
                    $bucket['end'],
                    false,
                );
            }
        }

        return [
            'labels' => array_map(static fn (array $bucket): string => $bucket['label'], $buckets),
            'datasets' => $datasets,
        ];
    }

    public function firstRecordedAt(): ?Carbon
    {
        $firstRollupDate = DesktopUpdateDailyRollup::query()->orderBy('date')->value('date');
        $firstEventAt = DesktopUpdateEvent::query()->orderBy('created_at')->value('created_at');

        return collect([$firstRollupDate, $firstEventAt])
            ->filter()
            ->map(fn (mixed $value): Carbon => Carbon::parse((string) $value)->timezone(timezone()))
            ->sort()
            ->first();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function rangeWindow(string $range): array
    {
        $normalizedRange = $this->normalizeRange($range);
        $now = now()->timezone(timezone());

        return match ($normalizedRange) {
            'today' => [$now->copy()->startOfDay(), $now->copy()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()],
            default => [$this->firstRecordedAt()?->copy()->startOfDay() ?? $now->copy()->startOfMonth(), $now->copy()],
        };
    }

    private function countBetween(
        DesktopUpdateEventType $eventType,
        Carbon $start,
        Carbon $end,
        bool $attributedOnly,
    ): int {
        if ($end->lt($start)) {
            return 0;
        }

        $todayStart = now()->timezone(timezone())->startOfDay();

        if ($end->lt($todayStart)) {
            return $this->rollupCount($eventType, $start, $end, $attributedOnly);
        }

        if ($start->gte($todayStart)) {
            return $this->rawCount($eventType, $start, $end, $attributedOnly);
        }

        return $this->rollupCount($eventType, $start, $todayStart->copy()->subDay()->endOfDay(), $attributedOnly)
            + $this->rawCount($eventType, $todayStart, $end, $attributedOnly);
    }

    private function rollupCount(
        DesktopUpdateEventType $eventType,
        Carbon $start,
        Carbon $end,
        bool $attributedOnly,
    ): int {
        if ($end->lt($start)) {
            return 0;
        }

        return (int) DesktopUpdateDailyRollup::query()
            ->where('event_type', $eventType->value)
            ->when($attributedOnly, fn ($query) => $query->where('attributed', true))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('total_events');
    }

    private function rawCount(
        DesktopUpdateEventType $eventType,
        Carbon $start,
        Carbon $end,
        bool $attributedOnly,
    ): int {
        return DesktopUpdateEvent::query()
            ->where('event_type', $eventType->value)
            ->when($attributedOnly, fn ($query) => $query->where('attributed', true))
            ->whereBetween('created_at', [$start->copy()->timezone('UTC'), $end->copy()->timezone('UTC')])
            ->count();
    }

    private function normalizeRange(?string $range): string
    {
        if (is_string($range) && array_key_exists($range, ActivityTimelineChart::RANGE_OPTIONS)) {
            return $range;
        }

        return 'this_month';
    }

    /**
     * @return list<array{start: Carbon, end: Carbon, label: string}>
     */
    private function resolveBuckets(string $range, Carbon $now): array
    {
        return match ($this->normalizeRange($range)) {
            'today' => $this->hourBuckets($now->copy()->startOfDay(), $now),
            'this_week' => $this->dayBuckets($now->copy()->startOfWeek(), $now, 'D'),
            'this_month' => $this->dayBuckets($now->copy()->startOfMonth(), $now, 'M j'),
            default => $this->lifetimeBuckets($now),
        };
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
        $firstRecordedAt = $this->firstRecordedAt();

        if ($firstRecordedAt === null) {
            return $this->recentMonthBuckets($now, 12);
        }

        $buckets = [];
        $cursor = $firstRecordedAt->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($now)) {
            $bucketEnd = $cursor->copy()->endOfMonth();

            if ($bucketEnd->greaterThan($now)) {
                $bucketEnd = $now->copy();
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
        $start = $now->copy()->startOfMonth()->subMonthsNoOverflow($months - 1);
        $buckets = [];
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($now)) {
            $bucketEnd = $cursor->copy()->endOfMonth();

            if ($bucketEnd->greaterThan($now)) {
                $bucketEnd = $now->copy();
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
