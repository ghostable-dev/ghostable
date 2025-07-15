<?php

namespace App\Environment;

use App\Environment\Models\Environment;
use App\Environment\Policies\EnvironmentPolicy;
use App\Environment\Registry\EnvironmentVariableRegistry;
use App\Environment\Validation\ValidationServiceProvider;
use App\Environment\View\Components\EnvTokenExpiryReminder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class EnvironmentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerVariables();

        $this->app->register(ValidationServiceProvider::class);
    }

    /**
     * Registers all known environment variable definitions into the singleton
     * EnvironmentVariableRegistry.
     *
     * This method scans the `app/Environment/Definitions` directory for PHP classes
     * that extend EnvironmentVariableDefinition, instantiates them, and registers each
     * into the registry. The registry is then bound as a singleton in the Laravel container,
     * making it available throughout the application.
     */
    private function registerVariables(): void
    {
        $this->app->singleton(EnvironmentVariableRegistry::class, function () {
            $registry = new EnvironmentVariableRegistry;
            $definitionNamespace = 'App\\Environment\\Definitions';
            $definitionPath = app_path('Environment/Definitions');
            foreach (scandir($definitionPath) as $file) {
                if (! Str::endsWith($file, '.php')) {
                    continue;
                }
                $class = $definitionNamespace.'\\'.Str::before($file, '.php');
                if (class_exists($class)) {
                    $definition = new $class;
                    $registry->register($definition);
                }
            }

            return $registry;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Blade::component('env-token-expiry-reminder', EnvTokenExpiryReminder::class);

        Gate::policy(Environment::class, EnvironmentPolicy::class);

        Relation::enforceMorphMap([
            'environment' => 'App\Environment\Models\Environment',
            'variable' => 'App\Environment\Models\EnvironmentVariable',
        ]);
    }
}
