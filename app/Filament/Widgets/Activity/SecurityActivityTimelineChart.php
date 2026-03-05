<?php

namespace App\Filament\Widgets\Activity;

use App\Core\Models\Activity;
use App\Filament\Resources\Core\SecurityActivity\SecurityActivityResource;
use Illuminate\Database\Eloquent\Builder;

class SecurityActivityTimelineChart extends ActivityTimelineChart
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Security Activity Trend';

    protected string $color = 'danger';

    protected function getActivityQuery(): Builder
    {
        return SecurityActivityResource::applySecurityScopeTo(Activity::query());
    }
}
