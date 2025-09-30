<?php

namespace App\Environment\Deployment\Handlers;

use App\Environment\Deployment\Entities\DeploymentData;
use App\Environment\Models\Environment;
use App\Project\Enums\DeploymentProvider;

class VaporDeploymentHandler extends LaravelDeploymentHandler
{
    public function toData(Environment $environment): DeploymentData
    {
        $this->setEnvironment($environment);

        return new DeploymentData(
            provider: DeploymentProvider::LARAVEL_VAPOR,
            standard: $this->standardVariables(),
            secret: $this->secretVariables(),
            encrypted: $this->usesEncryptedDelivery ? $this->encryptedEnvFile() : null
        );
    }
}
