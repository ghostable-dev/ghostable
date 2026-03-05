<?php

namespace App\Filament\Widgets\Activity\Concerns;

use App\Filament\Widgets\Activity\ActivityTimelineChart;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

trait InteractsWithActivityRange
{
    public string $range = 'this_month';

    #[On(ActivityTimelineChart::RANGE_CHANGED_EVENT)]
    public function syncActivityRange(string $range): void
    {
        $this->range = $this->normalizeActivityRange($range);

        if (property_exists($this, 'cachedStats')) {
            $this->cachedStats = null;
        }
    }

    protected function normalizeActivityRange(?string $range): string
    {
        if (is_string($range) && array_key_exists($range, ActivityTimelineChart::RANGE_OPTIONS)) {
            return $range;
        }

        return 'this_month';
    }

    protected function activityRangeLabel(): string
    {
        return ActivityTimelineChart::RANGE_OPTIONS[$this->normalizeActivityRange($this->range)];
    }

    protected function applyActivityDateRange(Builder $query, string $column = 'created_at'): Builder
    {
        $range = $this->normalizeActivityRange($this->range);
        $now = now()->timezone(timezone());

        return match ($range) {
            'today' => $query->whereBetween($column, [
                $now->copy()->startOfDay()->timezone('UTC'),
                $now->copy()->timezone('UTC'),
            ]),
            'this_week' => $query->whereBetween($column, [
                $now->copy()->startOfWeek()->timezone('UTC'),
                $now->copy()->timezone('UTC'),
            ]),
            'this_month' => $query->whereBetween($column, [
                $now->copy()->startOfMonth()->timezone('UTC'),
                $now->copy()->timezone('UTC'),
            ]),
            default => $query,
        };
    }
}
