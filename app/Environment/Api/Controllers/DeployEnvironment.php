<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\RenderEnvFile;
use App\Environment\Models\Environment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;

class DeployEnvironment extends Controller
{
    /**
     * Render and return the environment variables for deployment.
     *
     * This endpoint uses a token-based authentication approach, where the token directly
     * references and authenticates a specific environment. It returns the environment
     * variables formatted as a `.env` file.
     *
     * Authorization: Token must resolve directly to an existing Environment.
     */
    public function __invoke(): Response
    {
        // Retrieve the environment directly associated with the provided token.
        /** @var Environment|null $environment */
        $environment = request()->user();

        if (! $environment) {
            throw new AuthorizationException('The provided token is invalid or does not correspond to any environment.');
        }

        // Generate and return the .env-formatted string content.
        $content = RenderEnvFile::handle(env: $environment);

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
