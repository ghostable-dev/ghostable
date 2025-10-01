<?php

namespace App\Environment\Deployment\Handlers;

use App\Environment\Deployment\Entities\DeploymentData;
use App\Environment\Models\Environment;
use App\Project\Enums\DeploymentProvider;
use Illuminate\Support\Collection;

class VaporDeploymentHandler extends LaravelDeploymentHandler
{
    public function toData(Environment $environment): DeploymentData
    {
        $this->setEnvironment(environment: $environment);

        return new DeploymentData(
            provider: DeploymentProvider::LARAVEL_VAPOR,
            standard: $this->standardVariables(),
            secret: $this->secretVariables(),
            encrypted: $this->encrypted ? $this->toEncryptedEnvString($this->nonSecretVariables()) : null
        );
    }

    protected function standardVariables(): Collection
    {
        return $this->encrypted ? collect([$this->encryptionKeyVariable()]) : $this->nonSecretVariables();
    }

    protected function nonSecretVariables(): Collection
    {
        return $this->variables
            ->filter(fn ($var) => ! $var->is_vapor_secret)
            ->values();
    }

    protected function secretVariables(): Collection
    {
        return $this->variables
            ->filter(fn ($var) => $var->is_vapor_secret)
            ->values();
    }
}
