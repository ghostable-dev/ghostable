<?php

namespace App\Auth;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class AuthServiceProdivder extends ServiceProvider
{
    public function register(): void
    {}

    public function boot(): void
    {
        Fortify::twoFactorChallengeView('auth.two-factor-challenge');
        
        RateLimiter::for('login', function (Request $request) {
            $username = Fortify::username();
            $throttleKey = str()->transliterate(
                str()->lower($request->input($username)).'|'.$request->ip()
            );
            return Limit::perMinute(5)->by($throttleKey);
        });
        
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
