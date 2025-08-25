<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Environment;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\RenderEnvFile;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Rules\ValidEnvFileFormat;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PullEnvironment extends Controller
{
    /**
     * Render and return the current environment variables as a .env-formatted string.
     *
     * The response is returned as plain text, suitable for writing directly to a `.env` file.
     *
     * Authorization: Requires 'ViewVariables' permission on the environment.
     */
    public function __invoke(Request $request, Project $project, string $name): Response
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, OrganizationPermission::ViewVariables]);

        $validated = $request->validate([
            'format' => ['nullable', new ValidEnvFileFormat],
        ]);

        $format = isset($validated['format'])
            ? EnvFileFormat::from($validated['format'])
            : null;

        $content = RenderEnvFile::handle(env: $env, format: $format);

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
