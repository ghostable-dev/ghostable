<?php

namespace App\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsFounder
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->isFounder()) {
            return $next($request);
        }

        return redirect('/');
    }
}
