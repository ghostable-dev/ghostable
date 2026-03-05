<?php

namespace App\Filament\Widgets\Activity;

use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Builder;

class OrganizationTimelineChart extends ActivityTimelineChart
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Organization Growth';

    protected string $color = 'success';

    protected function getActivityQuery(): Builder
    {
        return Organization::query();
    }
}
