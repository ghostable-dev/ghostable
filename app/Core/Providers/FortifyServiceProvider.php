<?php

namespace App\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::twoFactorChallengeView('auth.two-factor-challenge');
    }
}
