<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Project;

use App\Api\Resources\Project\ProjectResource;
use App\Core\Http\Controllers\Controller;
use App\Project\Models\Project;
use Illuminate\Http\Resources\Json\JsonResource;

final class GetProject extends Controller
{
    /**
     * Display the specified project.
     *
     * Authorization: Requires 'view' permission on the project.
     */
    public function __invoke(Project $project): JsonResource
    {
        $this->authorize('view', $project);

        return new ProjectResource($project->load('environments'));
    }
}
