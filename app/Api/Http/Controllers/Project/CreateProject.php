<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Project;

use App\Api\Resources\Project\ProjectResource;
use App\Core\Http\Controllers\Controller;
use App\Environment\Enums\EnvironmentType;
use App\Project\Actions\CreateProject as CreateProjectAction;
use App\Project\Models\Project;
use App\Project\Rules\ProjectRules;
use App\Team\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CreateProject extends Controller
{
    /**
     * Create a new project for the given team.
     *
     * Authorization: Requires 'create' permission on the team.
     */
    public function __invoke(Request $request, Team $team): JsonResource
    {
        $this->authorize('create', [Project::class, $team]);

        $validated = $request->validate(ProjectRules::createRules($team));

        $project = app(CreateProjectAction::class)->handle(
            name: $validated['name'],
            team: $team
        );

        $project->environments()->createMany([
            ['name' => 'local', 'type' => EnvironmentType::LOCAL],
        ]);

        return new ProjectResource($project->load('environments'));
    }
}
