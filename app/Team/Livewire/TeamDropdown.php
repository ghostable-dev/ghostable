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
                <x-drop-button 
                    href="{{ route('dashboard') }}">
                    <span class="block max-w-[8rem] truncate text-left">
                        {{ $this->currentTeam?->name }}
                    </span>
                    <x-slot name="menu">
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
                    </x-slot>
                </x-drop-button>
                <livewire:team.livewire.team-create-modal/>
            </div>
        BLADE;
    }
}
