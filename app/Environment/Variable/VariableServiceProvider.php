<?php

namespace App\Environment\Variable;

use App\Environment\Variable\Events\VariableUpdated;
use App\Environment\Variable\Listeners\SendVariableUpdatedNotification;
use App\Environment\Variable\Registry\VariableRegistry;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class VariableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VariableRegistry::class, function () {
            $registry = new VariableRegistry;
            $definitionNamespace = 'App\\Environment\\Variable\\Definitions';
            $definitionPath = app_path('Environment/Variable/Definitions');
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

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'variable' => 'App\Environment\Variable\Models\EnvironmentVariable',
        ]);

        Event::listen(
            VariableUpdated::class,
            SendVariableUpdatedNotification::class
        );
    }
}
