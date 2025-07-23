<?php

namespace App\Team\Livewire;

use App\Team\Actions\UpdateTeamName;
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
        $this->authorize('manageSettings', $this->team);

        app(UpdateTeamName::class)->handle($this->team, $this->name);

        $this->team->refresh();

        $this->dispatch('name-updated', name: $this->name);
    }

    #[Computed(persist: true)]
    public function canEditName(): bool
    {
        return ! $this->team->isPersonal();
    }

    #[Computed()]
    public function team(): Team
    {
        return Auth::user()->currentTeam();
    }

    public function render()
    {
        return view('team.team-general-settings');
    }
}
