<?php

namespace App\Filament\Widgets\Activity;

use App\Core\Models\Activity;
use App\Filament\Resources\Core\Activity\ActivityResource;
use Illuminate\Database\Eloquent\Builder;

class AllActivityTimelineChart extends ActivityTimelineChart
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Activity Trend';

    protected string $color = 'gray';

    protected function getActivityQuery(): Builder
    {
        return ActivityResource::applyActivityScopeTo(Activity::query());
    }
}
