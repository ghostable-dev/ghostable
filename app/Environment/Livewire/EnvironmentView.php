<?php

namespace App\Environment\Livewire;

use App\Environment\Models\Environment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EnvironmentView extends Component
{
    #[Locked]
    public string $envId;
    
    public array $showing = [];
    
    public function mount(Environment $environment): void
    {
        $this->envId = $environment->id;
    }
    
    #[Computed()]
    public function environment(): Environment
    {
        return Environment::findOrFail($this->envId);
    }
    
    public function toggleShow(string $id): void
    {
        // Flip visibility for the given ID
        $this->showing[$id] = !($this->showing[$id] ?? false);
    }

    public function render()
    {
        return view('environment.environment-view');
    }
}
