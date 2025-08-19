<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Team;

use App\Api\Resources\Team\TeamResource;
use Illuminate\Http\Request;

final class GetOwnedTeams
{
    /**
     * Get the authenticated users "owned" teams.
     */
    public function __invoke(Request $request)
    {
        $teams = $request->user()->ownedTeams;

        return TeamResource::collection($teams);
    }
}
