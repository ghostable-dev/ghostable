<?php

namespace App\Environment\Livewire;

use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class EnvironmentView extends Component
{
    #[Locked]
    public string $envId;

    /**
     * The currently active tab in the environment viewer.
     *
     * Defaults to 'variables'. Other valid values may include
     * 'secrets', 'general', and 'access'.
     */
    public string $tab = 'variables';

    public function mount(Environment $environment): void
    {
        $this->authorize('view', $environment);

        $this->envId = $environment->id;
    }

    /**
     * Retrieve the environment instance based on the bound environment ID.
     *
     * Used for accessing environment-specific data across the component.
     */
    #[Computed]
    public function environment(): Environment
    {
        return ResolveEnvironment::onceWithContext($this->envId);
    }

    /**
     * Refresh the environment instance when the 'environment-updated' event is triggered.
     *
     * This ensures the latest data is available after an update occurs elsewhere.
     */
    #[On('environment-updated')]
    public function refreshEnvironment(): void
    {
        $this->environment->refresh();
    }

    public function render()
    {
        return view('environment.environment-view');
    }
}
