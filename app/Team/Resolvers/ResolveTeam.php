<?php

namespace App\Team\Resolvers;

use App\Team\Models\Team;

class ResolveTeam
{
    public static function onceWithContext(string $teamId): Team
    {
        return once(function () use ($teamId) {
            return Team::with([])->findOrFail($teamId);
        }, "team:withContext:{$teamId}");
    }
}
