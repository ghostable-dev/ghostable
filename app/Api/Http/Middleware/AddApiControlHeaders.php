<?php

declare(strict_types=1);

namespace App\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class AddApiControlHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Ghostable-Api-Versions', 'v1');

        if ($endpoint = $request->route()?->uri()) {
            Cache::increment("call:{$endpoint}");
        }

        // if (str_starts_with($request->path(), 'api/v1')) {
        //     $response->headers->set('X-Ghostable-Deprecation', 'TODO: deprecation date');
        // }

        return $response;
    }
}
