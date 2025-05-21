<?php

namespace App\Account\Api\Controllers;

use App\Account\Api\Resources\TeamResource;
use App\Account\Models\Team;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GetTeam extends Controller
{
    /**
     * Get the team resource.
     */
    public function __invoke(Request $request, Team $team)
    {
        $request->user()->can('view', $team);
        
        return TeamResource::collection($team);
    }
}
