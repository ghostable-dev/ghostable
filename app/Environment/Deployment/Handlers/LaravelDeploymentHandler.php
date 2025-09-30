<?php

namespace App\Environment\Deployment\Handlers;

use App\Environment\Actions\RenderEnvironmentVariables;
use App\Environment\Models\Environment;
use App\Environment\Variable\Enums\DeliveryMode;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Collection;

abstract class LaravelDeploymentHandler extends DeploymentHandler
{
    protected bool $usesEncryptedDelivery;

    protected ?string $encryptionKey = null;

    protected ?Encrypter $encrypter = null;

    public function setEnvironment(Environment $environment): static
    {
        parent::setEnvironment($environment);

        $this->usesEncryptedDelivery = $this->variables->contains('delivery_mode', DeliveryMode::ENCRYPTED);

        $this->encryptionKey = $environment->encryptionKeyString();

        $this->encrypter = $environment->encrypter();

        return $this;
    }

    protected function standardVariables(): Collection
    {
        $variables = parent::standardVariables();

        if ($this->usesEncryptedDelivery) {
            $variables->prepend($this->encryptionKeyVariable());
        }

        return $variables;
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

    protected function secretVariables(): Collection
    {
        return $this->filteredByDeliveryMode(DeliveryMode::SECRET);
    }

    protected function encryptedEnvFile(): string
    {
        $plaintext = resolve(RenderEnvironmentVariables::class)->handle($this->encryptedVariables());

        return $this->encrypter->encryptString($plaintext);
    }

    protected function encryptedVariables(): Collection
    {
        return $this->filteredByDeliveryMode(DeliveryMode::ENCRYPTED);
    }
}
