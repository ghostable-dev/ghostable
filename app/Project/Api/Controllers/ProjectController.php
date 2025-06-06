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
        request()->user()->can('view', $team);
        
        $projects = Project::query()
            ->where('team_id', $team->id)
            ->with('environments')
            ->get();

        return ProjectResource::collection($projects);
    }

    public function show(Project $project)
    {
        request()->user()->can('view', $project);

        return new ProjectResource($project->load('environments'));
    }

    public function store(Request $request, Team $team)
    {
        request()->user()->can('create', [Project::class, $team]);
        
        $validated = request()->validate(
            rules: ProjectRules::createRules($team), 
            params: $request->input()
        );
        
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
