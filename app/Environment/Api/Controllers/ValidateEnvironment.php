<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Validation\Actions\ValidateEnvironment as Validate;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ValidateEnvironment extends Controller
{
    public function __invoke(Project $project, string $name): JsonResource|JsonResponse
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('view', $env);

        app(Validate::class)->handle($env);

        return response()->json();
    }
}
