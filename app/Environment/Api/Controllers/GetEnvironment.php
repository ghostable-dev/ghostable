<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Api\Resources\EnvironmentResource;
use App\Project\Models\Project;
use Illuminate\Http\Resources\Json\JsonResource;

class GetEnvironment extends Controller
{
    /**
     * Display metadata about the specified environment.
     *
     * Returns structured JSON representing the environment record,
     * including basic metadata and relationships.
     *
     * Authorization: Requires 'view' permission on the environment.
     */
    public function __invoke(Project $project, string $name): JsonResource
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('view', $env);

        return new EnvironmentResource($env);
    }
}
