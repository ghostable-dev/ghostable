<?php

namespace App\Account\Providers;

use App\Account\Console\Commands\AppSetup;
use App\Account\Models\Team;
use App\Account\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Team::class, TeamPolicy::class);
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                AppSetup::class,
            ]);
        }
    }
}
