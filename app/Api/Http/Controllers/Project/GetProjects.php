<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Project;

use App\Api\Resources\Project\ProjectResource;
use App\Core\Http\Controllers\Controller;
use App\Project\Models\Project;
use App\Team\Models\Team;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GetProjects extends Controller
{
    /**
     * Display all projects belonging to the given team.
     *
     * Authorization: Requires 'view' permission on the team.
     */
    public function __invoke(Team $team): AnonymousResourceCollection
    {
        $this->authorize('view', $team);

        $projects = Project::query()
            ->where('team_id', $team->id)
            ->with('environments')
            ->get();

        return ProjectResource::collection($projects);
    }
}
