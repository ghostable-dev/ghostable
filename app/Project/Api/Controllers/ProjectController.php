<?php

namespace App\Project\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Enums\EnvironmentType;
use App\Project\Actions\CreateProject;
use App\Project\Api\Resources\ProjectResource;
use App\Project\Models\Project;
use App\Project\Rules\ProjectRules;
use App\Team\Models\Team;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Team $team)
    {
        $this->authorize('view', $team);

        $projects = Project::query()
            ->where('team_id', $team->id)
            ->with('environments')
            ->get();

        return ProjectResource::collection($projects);
    }

    public function show(Project $project)
    {
        $this->authorize('view', $project);

        return new ProjectResource($project->load('environments'));
    }

    public function store(Request $request, Team $team)
    {
        $this->authorize('create', [Project::class, $team]);

        $validated = request()->validate(ProjectRules::createRules($team));

        $project = app(CreateProject::class)->handle(
            name: $validated['name'],
            team: $team
        );

        $project->environments()->createMany([
            ['name' => 'local', 'type' => EnvironmentType::LOCAL],
        ]);

        return new ProjectResource($project->load('environments'));
    }
}
