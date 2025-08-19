<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Environment;

use App\Api\Resources\Environment\EnvironmentResource;
use App\Core\Http\Controllers\Controller;
use App\Project\Models\Project;
use Illuminate\Http\Resources\Json\JsonResource;

final class GetEnvironment extends Controller
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
