<?php

namespace App\Filament\Widgets\Activity;

use App\Core\Models\Activity;
use App\Filament\Resources\Core\ApiActivity\ApiActivityResource;
use Illuminate\Database\Eloquent\Builder;

class ApiActivityTimelineChart extends ActivityTimelineChart
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'API Activity Trend';

    protected string $color = 'warning';

    protected function getActivityQuery(): Builder
    {
        return ApiActivityResource::applyApiScopeTo(Activity::query());
    }
}
