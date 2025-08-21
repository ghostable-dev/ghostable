<?php

namespace App\Environment;

use App\Environment\Events\EnvironmentBaseChanged;
use App\Environment\Events\EnvironmentCreated;
use App\Environment\Events\EnvironmentDeleted;
use App\Environment\Events\EnvironmentEvent;
use App\Environment\Events\EnvironmentNameChanged;
use App\Environment\Console\Commands\ReencryptEnvironmentData;
use App\Environment\Listeners\SendEnvironmentActivityNotification;
use App\Environment\Models\Environment;
use App\Environment\Policies\EnvironmentPolicy;
use App\Environment\Resolvers\EnvironmentAncestryResolver;
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

        // Send activity notification
        Event::listen(
            [EnvironmentCreated::class, EnvironmentDeleted::class],
            SendEnvironmentActivityNotification::class
        );

        // Bust ancestry resolver cache
        Event::listen(
            [
                EnvironmentCreated::class,
                EnvironmentDeleted::class,
                EnvironmentBaseChanged::class,
                EnvironmentNameChanged::class,
            ],
            function (EnvironmentEvent $event) {
                resolve(EnvironmentAncestryResolver::class)->bust($event->environment);
            }
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                ReencryptEnvironmentData::class,
            ]);
        }
    }
}
