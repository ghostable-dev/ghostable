<?php

namespace App\Account\Api\Controllers;

use App\Account\Api\Resources\TeamResource;
use App\Account\Models\Team;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $projects = $request->user()->teams;

        return TeamResource::collection($projects);
    }

    public function show(Team $team)
    {
        return new TeamResource($team);
    }
}
