<?php

namespace App\Environment\Variable;

use App\Environment\Variable\Registry\VariableRegistry;
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

    public function boot(): void {}
}
