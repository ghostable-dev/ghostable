<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\CreateEnv;
use App\Environment\Actions\PushAndValidateEnvironment;
use App\Environment\Actions\RenderEnvFile;
use App\Environment\Api\Resources\EnvironmentResource;
use App\Environment\Api\Resources\PushResultResource;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Rules\EnvironmentRules;
use App\Project\Models\Project;
use App\Team\Enums\TeamPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class EnvironmentController extends Controller
{
    /**
     * Display metadata about the specified environment.
     *
     * Returns structured JSON representing the environment record,
     * including basic metadata and relationships.
     *
     * Authorization: Requires 'view' permission on the environment.
     */
    public function show(Project $project, string $name): JsonResource
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('view', $env);

        return new EnvironmentResource($env);
    }

    /**
     * Push a new set of environment variables to the given environment.
     *
     * Accepts raw .env lines as input and performs a diff-aware update:
     * only changed keys are updated, added, or removed.
     *
     * Returns a structured JSON result including change counts and status.
     *
     * Authorization: Requires 'update' permission on the environment.
     */
    public function push(Project $project, string $name): JsonResource|JsonResponse
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, TeamPermission::PushFile]);

        try {
            $vars = request()->input('vars', []);

            $result = app(PushAndValidateEnvironment::class)->handle(env: $env, incomingRaw: $vars);

            return new PushResultResource($result);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Render and return the current environment variables as a .env-formatted string.
     *
     * The response is returned as plain text, suitable for writing directly to a `.env` file.
     *
     * Authorization: Requires 'view' permission on the environment.
     */
    public function pull(Project $project, string $name): Response
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, TeamPermission::ViewVariables]);

        $content = RenderEnvFile::handle(env: $env);

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    public function store(Project $project): JsonResource
    {
        $this->authorize('perform', [$project, TeamPermission::CreateEnvironments]);

        $validated = request()->validate(
            EnvironmentRules::createRules($project),
        );

        $env = app(CreateEnv::class)->handle(
            name: $validated['name'],
            type: EnvironmentType::from($validated['type']),
            project: $project
        );

        return new EnvironmentResource($env);
    }
}
