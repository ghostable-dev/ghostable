<?php

namespace App\Core\Actions;

use App\Team\Enums\TeamRole;
use App\Team\Models\Team;
use Illuminate\Support\Collection;

class GetNotifiableTeamUsers
{
    public static function handle(Team $team): Collection
    {
        return $team->users()
            ->wherePivot('role', '!=', TeamRole::BILLING_ONLY)
            ->get();
    }
}
