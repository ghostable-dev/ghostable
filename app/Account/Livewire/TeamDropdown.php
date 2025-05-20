<?php

namespace App\Account\Livewire;

use App\Account\Actions\SwitchToTeam;
use App\Account\Models\Team;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamDropdown extends Component
{
    #[Computed()]
    public function currentTeam(): ?Team
    {
        return auth()->user()->currentTeam();
    }
    
    #[Computed()]
    public function teams(): Collection
    {
        return auth()->user()->teams;
    }
    
    public function switchToTeam(Team $team): void
    {
        SwitchToTeam::handle($team);
        
        Flux::toast("Switched to the '{$team->name}' team.");
    }
    
    public function render()
    {
        return <<<'BLADE'
            <flux:dropdown position="bottom" align="start">
                <flux:button class="w-full" icon:trailing="chevron-down">
                    {{ $this->currentTeam?->name }}
                </flux:button>
                <flux:menu>
                    <flux:menu.group heading="Manage Team">
                        <flux:menu.item>Settings</flux:menu.item>
                        <flux:modal.trigger name="create-team">
                            <flux:menu.item>
                                Create New Team
                            </flux:menu.item>
                        </flux:modal.trigger>
                    </flux:menu.group>
                    <flux:menu.group heading="Switch Teams">
                        @foreach($this->teams as $team)
                            @if($this->currentTeam?->id === $team->id)
                                <flux:menu.item wire:key="team-{{ $team->id }}">
                                    {{ $team->name }} (current)
                                </flux:menu.item>
                            @else
                                <flux:menu.item 
                                    wire:click="switchToTeam('{{ $team->id }}')" 
                                    wire:key="team-{{ $team->id }}">
                                    {{ $team->name }}
                                </flux:menu.item>
                            @endif
                            
                        @endforeach
                    </flux:menu.group>
                </flux:menu>
            </flux:dropdown>
        BLADE;
    }
}
