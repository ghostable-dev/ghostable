<?php

namespace App\Billing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasNoActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->organization->subscribed()) {
            return redirect()->route('organization.settings.billing', $request->organization);
        }

        return $next($request);
    }
}
