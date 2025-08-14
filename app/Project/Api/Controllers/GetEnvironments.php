<?php

namespace App\Project\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Api\Resources\EnvironmentResource;
use App\Project\Models\Project;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GetEnvironments extends Controller
{
    /**
     * Display all environments belonging to the given project.
     *
     * Authorization: Requires 'view' permission on the project.
     */
    public function __invoke(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        return EnvironmentResource::collection(
            $project->environments()->get()
        );
    }
}
