<?php

namespace App\Team\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Team\Api\Resources\TeamResource;
use App\Team\Models\Team;
use Illuminate\Http\Request;

class GetTeam extends Controller
{
    /**
     * Get the team resource.
     */
    public function __invoke(Request $request, Team $team)
    {
        $this->authorize('view', $team);
        
        //$request->user()->can('view', $team);

        return new TeamResource($team);
    }
}
