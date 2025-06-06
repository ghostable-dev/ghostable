<?php

namespace App\Environment;

use App\Environment\Models\Environment;
use App\Environment\Policies\EnvironmentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class EnvironmentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::policy(Environment::class, EnvironmentPolicy::class);
    }
}
