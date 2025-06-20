<?php

namespace App\Environment\Livewire;

use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

abstract class EnvironmentComponent extends Component
{
    #[Locked]
    public string $environmentId;

    public function mount(Environment $environment): void
    {
        $this->environmentId = $environment->id;
        
        //$this->authorize('view', $environment);
    }

    /**
     * Retrieve the current environment instance
     * based on the bound environment ID.
     */
    #[Computed]
    public function environment(): Environment
    {
        return ResolveEnvironment::onceWithContext($this->environmentId);
    }
}
