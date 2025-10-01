<?php

namespace App\Environment\Deployment\Handlers;

use App\Environment\Deployment\Entities\DeploymentData;
use App\Environment\Models\Environment;
use App\Project\Enums\DeploymentProvider;

class DefaultDeploymentHandler extends DeploymentHandler
{
    public function toData(Environment $environment): DeploymentData
    {
        $this->setEnvironment(environment: $environment);

        return new DeploymentData(
            provider: DeploymentProvider::OTHER,
            standard: $this->variables,
        );
    }
}
