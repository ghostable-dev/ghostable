<?php

namespace App\Environment\Deployment\Handlers;

use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Deployment\Contracts\DeploymentProviderHandler;
use App\Environment\Models\Environment;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Collection;

abstract class DeploymentHandler implements DeploymentProviderHandler
{
    protected Environment $environment;

    protected bool $encrypted;

    protected ?string $encryptionKey = null;

    protected ?Encrypter $encrypter = null;

    protected Collection $variables;

    public function setEnvironment(Environment $environment, bool $encrypted = false): static
    {
        $this->environment = $environment;

        $this->encrypted = $encrypted;

        if ($this->encrypted) {
            $this->encryptionKey = $environment->encryptionKeyString();
            $this->encrypter = $environment->encrypter();
        }

        $this->variables = resolve(ResolveEnvironmentVariables::class)->handle($environment);

        return $this;
    }

    protected function standardVariables(): Collection
    {
        return $this->nonSecretVariables();
    }

    protected function secretVariables(): Collection
    {
        return $this->variables
            ->filter(fn ($var) => (bool) $var->vapor_secret)
            ->values();
    }

    protected function nonSecretVariables(): Collection
    {
        return $this->variables
            ->filter(fn ($var) => ! $var->vapor_secret)
            ->values();
    }
}
