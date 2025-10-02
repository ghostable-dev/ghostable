<?php

namespace App\Environment\Deployment\Handlers;

use App\Environment\Actions\RenderEnvironmentVariables;
use App\Environment\Deployment\Contracts\SupportsEncryptedDeployment;
use App\Environment\Models\Environment;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Collection;

abstract class LaravelDeploymentHandler extends DeploymentHandler implements SupportsEncryptedDeployment
{
    protected bool $encrypted = false;

    protected ?string $encryptionKey = null;

    protected ?Encrypter $encrypter = null;

    public function enableEncryption(Environment $environment): void
    {
        $this->encrypted = true;
        $this->encryptionKey = $environment->encryptionKeyString();
        $this->encrypter = $environment->encrypter(cipher: 'AES-256-CBC');
    }

    protected function standardVariables(): Collection
    {
        return $this->encrypted ? collect([$this->encryptionKeyVariable()]) : $this->variables;
    }

    protected function encryptionKeyVariable(string $key = 'LARAVEL_ENV_ENCRYPTION_KEY'): object
    {
        return (object) [
            'id' => uniqid(),
            'key' => $key,
            'value' => $this->encryptionKey,
            'is_commented' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    protected function toEncryptedEnvString(Collection $variables): string
    {
        $plaintext = resolve(RenderEnvironmentVariables::class)->handle($variables);

        return $this->encrypter->encrypt(value: $plaintext, serialize: true);
    }
}
