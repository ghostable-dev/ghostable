<?php

namespace App\Environment\Deployment\Handlers;

use App\Environment\Deployment\Entities\DeploymentData;
use App\Environment\Models\Environment;
use App\Project\Enums\DeploymentProvider;

class CloudDeploymentHandler extends LaravelDeploymentHandler
{
    public function toData(Environment $environment, bool $encrypted = false): DeploymentData
    {
        $this->setEnvironment(environment: $environment, encrypted: $encrypted);

        return new DeploymentData(
            provider: DeploymentProvider::LARAVEL_CLOUD,
            standard: $this->standardVariables(),
            encrypted: $encrypted ? $this->encryptedEnvFile() : null
        );
    }
}
