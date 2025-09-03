<?php

namespace App\Core\Providers;

use App\Core\View\Components\SeoMeta;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('seo-meta', SeoMeta::class);

        RateLimiter::for('contact', function (Request $request) {
            return [Limit::perMinute(5)->by($request->ip())];
        });
    }
}
