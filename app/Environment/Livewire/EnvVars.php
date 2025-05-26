<?php

namespace App\Environment\Livewire;

use App\Account\Enums\Permission;
use App\Environment\Models\Environment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EnvVars extends Component
{
    #[Locked]
    public string $envId;
    
    public function mount(Environment $environment): void
    {
        $this->envId = $environment->id;
    }
    
    #[Computed()]
    public function env(): Environment
    {
        return Environment::firstOrFail($this->envId);
    }

    public function render()
    {
        return view('environment.env-vars');
    }
}
