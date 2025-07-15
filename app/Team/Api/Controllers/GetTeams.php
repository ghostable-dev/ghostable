<?php

namespace App\Team\Api\Controllers;

use App\Team\Api\Resources\TeamResource;
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
