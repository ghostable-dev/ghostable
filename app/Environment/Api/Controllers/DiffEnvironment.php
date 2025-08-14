<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\DiffEnvironment as DiffAction;
use App\Environment\Api\Resources\DiffResultResource;
use App\Project\Models\Project;
use App\Team\Enums\TeamPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class DiffEnvironment extends Controller
{
    /**
     * Compare incoming environment variables to the current environment.
     *
     * Returns arrays of added, updated, and removed variables without applying any changes.
     *
     * Authorization: Requires 'PushFile' permission on the environment.
     */
    public function __invoke(Project $project, string $name): JsonResource|JsonResponse
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, TeamPermission::PushFile]);

        $vars = request()->input('vars', []);

        $result = app(DiffAction::class)->handle(
            env: $env,
            incomingRaw: $vars
        );

        return new DiffResultResource($result);
    }
}
