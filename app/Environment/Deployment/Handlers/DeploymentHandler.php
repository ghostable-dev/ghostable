<?php

namespace App\Environment\Deployment\Handlers;

use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Deployment\Contracts\DeploymentProviderHandler;
use App\Environment\Models\Environment;
use Illuminate\Support\Collection;

abstract class DeploymentHandler implements DeploymentProviderHandler
{
    protected Environment $environment;

    protected Collection $variables;

    public function setEnvironment(Environment $environment): static
    {
        $this->environment = $environment;

        $this->variables = resolve(ResolveEnvironmentVariables::class)->handle($environment);

        return $this;
    }
}
