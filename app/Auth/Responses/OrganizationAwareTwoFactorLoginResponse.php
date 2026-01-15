<?php

namespace App\Auth\Responses;

use App\Auth\Actions\LogLoginActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;

class OrganizationAwareTwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = Auth::user();

        if ($user?->isSuspended() || $user?->isLocked()) {
            $message = $user?->isSuspended()
                ? 'Your account is suspended.'
                : 'Your account is locked.';

            if ($user) {
                app(LogLoginActivity::class)->failed(
                    user: $user,
                    email: $user->email,
                    reason: $user->isSuspended() ? 'suspended' : 'locked'
                );
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->wantsJson()
                ? new JsonResponse(['message' => $message], 403)
                : redirect()->route('login')->withErrors(['email' => $message]);
        }

        if ($user && $user->organizations()->count() > 1) {
            $request->session()->put('show-organization-switcher', true);
        }

        if ($user) {
            app(LogLoginActivity::class)->successful($user);
        }

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended(Fortify::redirects('login'));
    }
}
