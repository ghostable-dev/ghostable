<?php

namespace App\Environment\Deployment;

use App\Environment\Deployment\Handlers\CloudDeploymentHandler;
use App\Environment\Deployment\Handlers\ForgeDeploymentHandler;
use App\Environment\Deployment\Handlers\VaporDeploymentHandler;
use App\Project\Enums\DeploymentProvider;
use Illuminate\Support\ServiceProvider;

class DeploymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DeploymentProviderResolver::class, function ($app) {
            return new DeploymentProviderResolver([
                DeploymentProvider::LARAVEL_CLOUD->value => $app->make(CloudDeploymentHandler::class),
                DeploymentProvider::LARAVEL_FORGE->value => $app->make(ForgeDeploymentHandler::class),
                DeploymentProvider::LARAVEL_VAPOR->value => $app->make(VaporDeploymentHandler::class),
            ]);
        });
    }

    public function boot(): void {}
}
