<?php

namespace App\Core\Providers;

use App\Core\View\SeoMeta;
use Illuminate\Support\Facades\Blade;
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
    }
}
