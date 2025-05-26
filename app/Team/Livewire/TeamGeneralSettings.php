<?php

namespace App\Team\Livewire;

use App\Team\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamGeneralSettings extends Component
{
    public string $name;
    
    public function mount(): void
    {   
        $this->name = $this->team->name;
    }
    
    public function updateTeamName(): void
    {
        $this->authorize('admin', $this->team);
        
        $this->team->update(['name' => $this->name]);
        
        $this->dispatch('name-updated', name: $this->name);
    }
    
    #[Computed(persist: true)]
    public function canEditName(): bool
    {
        return !$this->team->isPersonal();
    }
    
    #[Computed()]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }
    
    public function render()
    {
        return view('team.settings.general-settings');
    }
}
