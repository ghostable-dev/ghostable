<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Team;

use App\Api\Resources\Team\TeamResource;
use App\Core\Http\Controllers\Controller;
use App\Team\Models\Team;
use Illuminate\Http\Request;

final class GetTeam extends Controller
{
    /**
     * Get the team resource.
     */
    public function __invoke(Request $request, Team $team)
    {
        $this->authorize('view', $team);

        return new TeamResource($team);
    }
}
