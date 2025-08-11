<?php

namespace App\Environment\Livewire;

use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Secret\Livewire\SecretsManager;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

class EnvironmentSecretsManager extends SecretsManager
{
    #[Locked]
    public string $environmentId;

    public function mount(Environment $environment): void
    {
        $this->authorize('view', $environment);

        $this->environmentId = $environment->id;

        $this->setOwner($environment);
    }

    #[Computed]
    public function environment(): Environment
    {
        return ResolveEnvironment::onceWithContext($this->environmentId);
    }

    public function render()
    {
        return view('environment.environment-secrets-manager');
    }
}
