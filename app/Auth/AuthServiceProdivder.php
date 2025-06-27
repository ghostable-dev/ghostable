<?php

namespace App\Auth;

use App\Auth\Models\PersonalAccessToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\Sanctum;

class AuthServiceProdivder extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        
        Relation::enforceMorphMap([
            'token' => 'App\Auth\Models\PersonalAccessToken'
        ]);
        
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
