<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Team;

use App\Api\Resources\Team\TeamResource;
use Illuminate\Http\Request;

final class GetTeams
{
    /**
     * Get the authenticated users "member" teams.
     */
    public function __invoke(Request $request)
    {
        $teams = $request->user()->teams;

        return TeamResource::collection($teams);
    }
}
