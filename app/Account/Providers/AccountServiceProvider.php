<?php

namespace App\Account\Providers;

use App\Account\Console\Commands\AppSetup;
use Illuminate\Support\ServiceProvider;

class AccountServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AppSetup::class,
            ]);
        }
    }
}
