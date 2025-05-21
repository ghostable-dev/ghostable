<?php

namespace App\Account\Api\Controllers;

use App\Account\Api\Resources\TeamResource;
use Illuminate\Http\Request;

class GetTeams
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
