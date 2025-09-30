<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Environment;

use App\Api\Resources\Environment\EnvironmentVariableResource;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\RenderEnvFile;
use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Rules\ValidEnvFileFormat;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

final class PullEnvironment extends Controller
{
    /**
     * Render and return current environment variables.
     *
     * Authorization: Requires 'ViewVariables' permission on the environment.
     */
    public function __invoke(Request $request, Project $project, string $name): Response|JsonResource
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, OrganizationPermission::ViewVariables]);

        $input = $this->validatedInput($request);

        if (! empty($input['json'])) {
            return $this->jsonResponse($env);
        }

        return $this->dotenvResponse($env, $input['format'] ?? null);
    }

    /**
     * Validate and normalize incoming query params.
     *
     * @return array{format?: string, json?: bool}
     */
    private function validatedInput(Request $request): array
    {
        $validated = $request->validate([
            'format' => ['nullable', new ValidEnvFileFormat],
            'json' => ['nullable', 'boolean'],
        ]);

        // Normalize boolean
        if (array_key_exists('json', $validated)) {
            $validated['json'] = filter_var($validated['json'], FILTER_VALIDATE_BOOLEAN);
        }

        return $validated;
    }

    /**
     * Return JSON representation.
     */
    private function jsonResponse($env): JsonResource
    {
        $vars = resolve(ResolveEnvironmentVariables::class)->handle($env);

        return EnvironmentVariableResource::collection($vars);
    }

    /**
     * Return plaintext dotenv.
     */
    private function dotenvResponse($env, ?string $format): Response
    {
        $formatEnum = $format ? EnvFileFormat::from($format) : null;

        $content = resolve(RenderEnvFile::class)->handle(env: $env, format: $formatEnum);

        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
