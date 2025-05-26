<?php

namespace App\Account\Providers;

use App\Account\Console\Commands\AppSetup;
use App\Account\View\Components\RoleSelect;
use Blade;
use Illuminate\Support\ServiceProvider;

class AccountServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(ACLServiceProvider::class);
    }

    public function boot(): void
    {
        Blade::component('role-select', RoleSelect::class);
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                AppSetup::class,
            ]);
        }
    }
}
