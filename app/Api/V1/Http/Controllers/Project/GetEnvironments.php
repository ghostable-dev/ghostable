<?php

declare(strict_types=1);

namespace App\Api\V1\Http\Controllers\Project;

use App\Api\Core\Resources\Environment\EnvironmentResource;
use App\Core\Http\Controllers\Controller;
use App\Project\Models\Project;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class GetEnvironments extends Controller
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
