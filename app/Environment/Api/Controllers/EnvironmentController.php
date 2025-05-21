<?php

namespace App\Environment\Api\Controllers;

use App\Actions\Environment\PushEnvironmentVariables;
use App\Environment\Api\Resources\EnvironmentResource;
use App\Environment\Models\Environment;
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
        
        PushEnvironmentVariables::handle(env: $env, incomingRaw: $vars);

        return response()->json();
    }

    public function pull(Environment $environment)
    {
        $vars = $environment->variables
            ->map(fn ($var) => ['key' => $var->key, 'value' => $var->value])
            ->all();

        return response()->json(
            ['vars' => $vars]
        );
    }
}
