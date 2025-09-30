<?php

namespace App\Environment\Deployment\Handlers;

use App\Environment\Actions\RenderEnvironmentVariables;
use App\Environment\Models\Environment;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Collection;

abstract class LaravelDeploymentHandler extends DeploymentHandler
{
    protected bool $useEncryptedDelivery = false;

    protected ?string $encryptionKey = null;

    protected ?Encrypter $encrypter = null;

    public function setEnvironment(Environment $environment): static
    {
        parent::setEnvironment($environment);

        $this->encryptionKey = $environment->encryptionKeyString();

        $this->encrypter = $environment->encrypter();

        return $this;
    }

    public function useEncryptedDelivery(bool $shouldEncrypt): static
    {
        $this->useEncryptedDelivery = $shouldEncrypt;

        return $this;
    }

    protected function standardVariables(): Collection
    {
        if ($this->useEncryptedDelivery) {
            return collect([$this->encryptionKeyVariable()]);
        }

        return $this->nonSecretVariables();
    }

    protected function encryptionKeyVariable(): object
    {
        return (object) [
            'id' => uniqid(),
            'key' => 'LARAVEL_ENV_ENCRYPTION_KEY',
            'value' => $this->encryptionKey,
            'is_commented' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    protected function encryptedEnvFile(): string
    {
        $plaintext = resolve(RenderEnvironmentVariables::class)->handle($this->encryptedVariables());

        return $this->encrypter->encryptString($plaintext);
    }

    protected function encryptedVariables(): Collection
    {
        if (! $this->useEncryptedDelivery) {
            return collect();
        }

        return $this->nonSecretVariables();
    }
}
