<?php

declare(strict_types=1);

namespace App\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApplyApiVersion
{
    public function handle(Request $request, Closure $next, string $version): Response
    {
        // TODO: bind version-specific implementations based on $version
        $request->attributes->set('api_version', $version);

        return $next($request);
    }
}
