<?php

namespace App\Account\Api\Controllers;

use App\Account\Api\Resources\TeamResource;
use Illuminate\Http\Request;

class GetOwnedTeams
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
