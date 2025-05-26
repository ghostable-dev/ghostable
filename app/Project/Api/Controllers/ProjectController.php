<?php

namespace App\Project\Api\Controllers;

use App\Team\Models\Team;
use App\Http\Controllers\Controller;
use App\Project\Api\Resources\ProjectResource;
use App\Project\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Team $team)
    {
        $projects = Project::query()
            ->where('team_id', $team->id)
            ->with('environments')
            ->get();

        return ProjectResource::collection($projects);
    }

    public function show(Project $project)
    {
        // $this->authorize('view', $project);

        return new ProjectResource($project->load('environments'));
    }

    public function store(Request $request, Team $team)
    {
        $name = $request->input('name');
        $description = $request->input('description');

        $project = new Project([
            'name' => $name,
            'description' => $description,
        ]);
        $project->team()->associate($team);
        $project->save();

        $project->environments()->createMany([
            ['name' => 'local'],
            ['name' => 'staging'],
            ['name' => 'production'],
        ]);

        return new ProjectResource($project->load('environments'));
    }
}
