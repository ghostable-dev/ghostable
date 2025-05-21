<?php

namespace App\Environment\Api\Controllers;

use App\Environment\Actions\PushEnvironmentVariables;
use App\Environment\Actions\RenderEnvFile;
use App\Environment\Api\Resources\EnvironmentResource;
use App\Http\Controllers\Controller;
use App\Project\Models\Project;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    public function show(Project $project, string $name)
    {
        $env = $project->environmentOrFail($name);

        return new EnvironmentResource($env);
    }

    public function push(Request $request, Project $project, string $name)
    {
        $env = $project->environmentOrFail($name);
        
        $request->user()->can('update', $env);
        
        $vars = $request->input('vars') ?? [];
        
        $result = PushEnvironmentVariables::handle(env: $env, incomingRaw: $vars);

        return response()->json($result);
    }

    public function pull(Request $request, Project $project, string $name)
    {
        $env = $project->environmentOrFail($name);
        
        $request->user()->can('view', $env);
        
        return response(
            RenderEnvFile::handle(env: $env), 
            200
            ['Content-Type' => 'text/plain']
        );
    }
}
