<?php

namespace App\Account;

use App\Account\Console\Commands\AppSetup;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AccountServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'user' => 'App\Account\Models\User',
        ]);
        
        if ($this->app->runningInConsole()) {
            $this->commands([
                AppSetup::class,
            ]);
        }
    }
}
