<?php

namespace App\Environment;

use App\Environment\Events\EnvironmentCreated;
use App\Environment\Events\EnvironmentDeleted;
use App\Environment\Listeners\SendEnvironmentActivityNotification;
use App\Environment\Models\Environment;
use App\Environment\Policies\EnvironmentPolicy;
use App\Environment\Validation\ValidationServiceProvider;
use App\Environment\Variable\VariableServiceProvider;
use App\Environment\View\Components\EnvTokenExpiryReminder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class EnvironmentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(VariableServiceProvider::class);

        $this->app->register(ValidationServiceProvider::class);
    }

    public function boot(): void
    {
        Blade::component('env-token-expiry-reminder', EnvTokenExpiryReminder::class);

        Gate::policy(Environment::class, EnvironmentPolicy::class);

        Relation::enforceMorphMap([
            'environment' => 'App\Environment\Models\Environment',
        ]);

        Event::listen(
            EnvironmentCreated::class,
            SendEnvironmentActivityNotification::class
        );

        Event::listen(
            EnvironmentDeleted::class,
            SendEnvironmentActivityNotification::class
        );
    }
}
