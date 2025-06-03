<?php

namespace App\Team\Livewire;

use App\Team\Actions\SwitchToTeam;
use App\Team\Models\Team;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamDropdown extends Component
{
    #[Computed]
    public function currentTeam(): Team
    {
        return Auth::user()->currentTeam();
    }

    #[Computed]
    public function teams(): Collection
    {
        return Auth::user()->teams;
    }

    #[Computed]
    public function switchableTeams(): Collection
    {
        return Auth::user()
            ->teams()
            ->where('teams.id', '!=', $this->currentTeam()->id)
            ->get();
    }

    public function switchToTeam(Team $team): void
    {
        SwitchToTeam::handle($team);

        Flux::toast("Switched to the '{$team->name}' team.");

        redirect()->route('dashboard');
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <flux:dropdown position="bottom" align="start">
                    <flux:dropdown position="bottom" align="start">
                        <flux:button class="w-full flex justify-start items-center px-4 py-2" icon:trailing="chevron-down">
                            <span class="truncate">{{ $this->currentTeam?->name }}</span>
                            <flux:spacer/>
                        </flux:button>
                    </flux:dropdown>
                    <flux:menu>
                        <flux:menu.group heading="Manage Team">
                            <flux:menu.item
                                :href="route('team.settings.index', $this->currentTeam)"
                                wire:navigate>Settings</flux:menu.item>
                            <flux:modal.trigger name="create-team">
                                <flux:menu.item>
                                    Create New Team
                                </flux:menu.item>
                            </flux:modal.trigger>
                        </flux:menu.group>
                        <flux:menu.group heading="Switch Teams">
                            @foreach($this->switchableTeams as $team)
                                <flux:menu.item 
                                    wire:click="switchToTeam('{{ $team->id }}')" 
                                    wire:key="team-{{ $team->id }}">
                                    {{ $team->name }}
                                </flux:menu.item>
                            @endforeach
                        </flux:menu.group>
                    </flux:menu>
                </flux:dropdown>
                <livewire:team.livewire.team-create-modal/>
            </div>
        BLADE;
    }
}
