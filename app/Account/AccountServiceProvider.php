<?php

namespace App\Account;

use App\Account\Console\Commands\AppSetup;
use Illuminate\Support\ServiceProvider;

class AccountServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AppSetup::class,
            ]);
        }
    }
}
