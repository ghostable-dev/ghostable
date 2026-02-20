<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project;

use App\Api\Core\Resources\Project\ProjectResource;
use App\Core\Http\Controllers\Controller;
use App\Project\Models\Project;

final class GetProject extends Controller
{
    public function __invoke(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return new ProjectResource($project);
    }
}
