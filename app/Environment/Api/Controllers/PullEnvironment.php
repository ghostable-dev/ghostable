<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\RenderEnvFile;
use App\Project\Models\Project;
use App\Team\Enums\TeamPermission;
use Illuminate\Http\Response;

class PullEnvironment extends Controller
{
    /**
     * Render and return the current environment variables as a .env-formatted string.
     *
     * The response is returned as plain text, suitable for writing directly to a `.env` file.
     *
     * Authorization: Requires 'ViewVariables' permission on the environment.
     */
    public function __invoke(Project $project, string $name): Response
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('perform', [$env, TeamPermission::ViewVariables]);

        $content = RenderEnvFile::handle(env: $env);

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
