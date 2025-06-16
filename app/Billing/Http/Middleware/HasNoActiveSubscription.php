<?php

namespace App\Billing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasNoActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->team->subscribed()) {
            return redirect()->route('team.settings.billing', $request->team);
        }

        return $next($request);
    }
}
