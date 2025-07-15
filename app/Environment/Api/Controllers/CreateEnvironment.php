<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\CreateEnv;
use App\Environment\Api\Resources\EnvironmentResource;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Rules\EnvironmentRules;
use App\Project\Models\Project;
use App\Team\Enums\TeamPermission;
use Illuminate\Http\Resources\Json\JsonResource;

class CreateEnvironment extends Controller
{
    public function __invoke(Project $project): JsonResource
    {
        $this->authorize('perform', [$project, TeamPermission::CreateEnvironments]);

        $validated = request()->validate(
            EnvironmentRules::createRules($project),
        );

        $env = app(CreateEnv::class)->handle(
            name: $validated['name'],
            type: EnvironmentType::from($validated['type']),
            project: $project
        );

        return new EnvironmentResource($env);
    }
}
