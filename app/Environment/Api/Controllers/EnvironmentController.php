<?php

namespace App\Environment\Api\Controllers;

use App\Environment\Api\Resources\EnvironmentResource;
use App\Environment\Models\Environment;
use App\Http\Controllers\Controller;
use App\Project\Models\Project;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    public function show(Project $project, string $name)
    {
        $env = $project->environments()->where('name', $name)->first();

        return new EnvironmentResource($env);
    }

    public function push(Request $request, Environment $environment)
    {
        $vars = $request->input('vars') ?? [];

        $environment->variables()->delete();

        $environment->variables()->createMany(
            collect($vars)->map(fn ($var) => [
                'key' => $var['key'],
                'value' => $var['value'] ?? '',
            ])
        );

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
