<?php

namespace App\Filament\Widgets\Activity;

use App\Account\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UserTimelineChart extends ActivityTimelineChart
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'User Growth';

    protected string $color = 'primary';

    protected function getActivityQuery(): Builder
    {
        return User::query();
    }
}
