<?php

namespace App\Auth\Http\Middleware;

use App\Account\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->isSuspended()) {
            return $this->reject($request, 'Your account is suspended.');
        }

        if ($user->isLocked()) {
            return $this->reject($request, 'Your account is locked.');
        }

        return $next($request);
    }

    protected function reject(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => $message,
        ]);
    }
}
