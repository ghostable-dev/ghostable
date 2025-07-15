<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\PushAndValidateEnvironment;
use App\Environment\Api\Resources\PushResultResource;
use App\Project\Models\Project;
use App\Team\Enums\TeamPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

class PushEnvironment extends Controller
{
    /**
     * Push a new set of environment variables to the given environment.
     *
     * Accepts raw .env lines as input and performs a diff-aware update:
     * only changed keys are updated, added, or removed.
     *
     * Returns a structured JSON result including change counts and status.
     *
     * Authorization: Requires 'PushFile' permission on the environment.
     */
    public function __invoke(Project $project, string $name): JsonResource|JsonResponse
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, TeamPermission::PushFile]);

        try {
            $vars = request()->input('vars', []);
            $result = app(PushAndValidateEnvironment::class)->handle(
                env: $env,
                incomingRaw: $vars
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        return new PushResultResource($result);
    }
}
