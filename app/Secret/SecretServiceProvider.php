<?php

namespace App\Secret;

use App\Secret\Console\Commands\MigrateProjectSecrets;
use App\Secret\Models\Secret;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class SecretServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'secret' => Secret::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrateProjectSecrets::class,
            ]);
        }
    }
}
