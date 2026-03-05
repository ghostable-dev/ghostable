<?php

namespace App\Filament\Widgets;

use App\Account\Models\User;
use App\Api\Usage\Models\ApiUsageDaily;
use App\Organization\Models\Organization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DashboardOverviewStats extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $windows = $this->monthWindows();
        $dailyBuckets = $this->currentMonthDailyBuckets($windows['current_end']);

        $usersThisMonth = $this->countUsersBetween($windows['current_start'], $windows['current_end']);
        $usersPreviousMonth = $this->countUsersBetween($windows['previous_start'], $windows['previous_end']);

        $loginsThisMonth = $this->countLoginsBetween($windows['current_start'], $windows['current_end']);
        $loginsPreviousMonth = $this->countLoginsBetween($windows['previous_start'], $windows['previous_end']);

        $organizationsThisMonth = $this->countOrganizationsBetween($windows['current_start'], $windows['current_end']);
        $organizationsPreviousMonth = $this->countOrganizationsBetween($windows['previous_start'], $windows['previous_end']);

        $apiCallsThisMonth = $this->countApiCallsBetween($windows['current_start'], $windows['current_end']);
        $apiCallsPreviousMonth = $this->countApiCallsBetween($windows['previous_start'], $windows['previous_end']);

        return [
            $this->makeOverviewStat(
                'New Users (This month)',
                $usersThisMonth,
                $usersPreviousMonth,
                $this->usersSeries($dailyBuckets),
            ),
            $this->makeOverviewStat(
                'Logins (This month)',
                $loginsThisMonth,
                $loginsPreviousMonth,
                $this->loginsSeries($dailyBuckets),
            ),
            $this->makeOverviewStat(
                'Organizations (This month)',
                $organizationsThisMonth,
                $organizationsPreviousMonth,
                $this->organizationsSeries($dailyBuckets),
            ),
            $this->makeOverviewStat(
                'API Calls (This month)',
                $apiCallsThisMonth,
                $apiCallsPreviousMonth,
                $this->apiCallsSeries($dailyBuckets),
            ),
        ];
    }

    /**
     * @param  list<int>  $chart
     */
    private function makeOverviewStat(string $label, int $current, int $previous, array $chart): Stat
    {
        [$description, $color] = $this->comparisonDescription($current, $previous);

        return Stat::make($label, number_format($current))
            ->description($description)
            ->descriptionColor($color)
            ->chart($chart)
            ->chartColor($color);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function comparisonDescription(int $current, int $previous): array
    {
        if ($current === $previous) {
            return ['No change vs last month', 'gray'];
        }

        if ($previous === 0) {
            return ['Up from 0 vs last month', 'success'];
        }

        $direction = $current > $previous ? 'Up' : 'Down';
        $color = $current > $previous ? 'success' : 'danger';
        $percentage = (int) round((abs($current - $previous) / $previous) * 100);

        return ["{$direction} {$percentage}% vs last month", $color];
    }

    /**
     * @return array{
     *     current_start: Carbon,
     *     current_end: Carbon,
     *     previous_start: Carbon,
     *     previous_end: Carbon
     * }
     */
    private function monthWindows(): array
    {
        $now = now()->timezone(timezone());
        $currentStart = $now->copy()->startOfMonth();
        $currentEnd = $now->copy();
        $durationInSeconds = max(0, $currentStart->diffInSeconds($currentEnd));

        $previousStart = $currentStart->copy()->subMonthNoOverflow();
        $previousEnd = $previousStart->copy()->addSeconds($durationInSeconds);

        return [
            'current_start' => $currentStart,
            'current_end' => $currentEnd,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
        ];
    }

    /**
     * @return list<array{start: Carbon, end: Carbon}>
     */
    private function currentMonthDailyBuckets(Carbon $end): array
    {
        $buckets = [];
        $cursor = $end->copy()->startOfMonth()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $bucketEnd = $cursor->copy()->endOfDay();

            if ($bucketEnd->greaterThan($end)) {
                $bucketEnd = $end->copy();
            }

            $buckets[] = [
                'start' => $cursor->copy(),
                'end' => $bucketEnd,
            ];

            $cursor->addDay();
        }

        return $buckets;
    }

    private function countUsersBetween(Carbon $start, Carbon $end): int
    {
        return User::query()
            ->whereBetween('created_at', [
                $start->copy()->timezone('UTC'),
                $end->copy()->timezone('UTC'),
            ])
            ->count();
    }

    private function countLoginsBetween(Carbon $start, Carbon $end): int
    {
        return User::query()
            ->whereNotNull('last_login')
            ->whereBetween('last_login', [
                $start->copy()->timezone('UTC'),
                $end->copy()->timezone('UTC'),
            ])
            ->count();
    }

    private function countOrganizationsBetween(Carbon $start, Carbon $end): int
    {
        return Organization::query()
            ->whereBetween('created_at', [
                $start->copy()->timezone('UTC'),
                $end->copy()->timezone('UTC'),
            ])
            ->count();
    }

    private function countApiCallsBetween(Carbon $start, Carbon $end): int
    {
        return (int) ApiUsageDaily::query()
            ->whereBetween('date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->sum('count');
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon}>  $buckets
     * @return list<int>
     */
    private function usersSeries(array $buckets): array
    {
        $values = [];

        foreach ($buckets as $bucket) {
            $values[] = User::query()
                ->whereBetween('created_at', [
                    $bucket['start']->copy()->timezone('UTC'),
                    $bucket['end']->copy()->timezone('UTC'),
                ])
                ->count();
        }

        return $values;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon}>  $buckets
     * @return list<int>
     */
    private function loginsSeries(array $buckets): array
    {
        $values = [];

        foreach ($buckets as $bucket) {
            $values[] = User::query()
                ->whereNotNull('last_login')
                ->whereBetween('last_login', [
                    $bucket['start']->copy()->timezone('UTC'),
                    $bucket['end']->copy()->timezone('UTC'),
                ])
                ->count();
        }

        return $values;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon}>  $buckets
     * @return list<int>
     */
    private function organizationsSeries(array $buckets): array
    {
        $values = [];

        foreach ($buckets as $bucket) {
            $values[] = Organization::query()
                ->whereBetween('created_at', [
                    $bucket['start']->copy()->timezone('UTC'),
                    $bucket['end']->copy()->timezone('UTC'),
                ])
                ->count();
        }

        return $values;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon}>  $buckets
     * @return list<int>
     */
    private function apiCallsSeries(array $buckets): array
    {
        $values = [];

        foreach ($buckets as $bucket) {
            $values[] = (int) ApiUsageDaily::query()
                ->whereDate('date', $bucket['start']->toDateString())
                ->sum('count');
        }

        return $values;
    }
}
