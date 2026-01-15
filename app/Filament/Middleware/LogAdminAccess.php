<?php

namespace App\Filament\Middleware;

use App\Auth\Actions\LogAccountSecurityActivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::user();

        if (! $user) {
            return $response;
        }

        $session = $request->session();
        $sessionKey = 'security.admin_access_logged';

        if ($session->get($sessionKey)) {
            return $response;
        }

        app(LogAccountSecurityActivity::class)->adminAccess($user, [
            'path' => $request->path(),
            'source' => 'filament',
        ]);

        $session->put($sessionKey, true);

        return $response;
    }
}
