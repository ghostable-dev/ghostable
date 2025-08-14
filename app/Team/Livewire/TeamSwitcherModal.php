<?php

namespace App\Team\Livewire;

use App\Team\Actions\SwitchToTeam;
use App\Team\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamSwitcherModal extends Component
{
    public bool $showing = false;

    public function mount(): void
    {
        $this->showing = session()->pull('show-team-switcher', false);
    }

    #[Computed]
    public function teams(): Collection
    {
        return Auth::user()->teams;
    }

    public function switchToTeam(Team $team): void
    {
        SwitchToTeam::handle($team);

        redirect()->route('dashboard');
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal wire:model="showing" :dismissible="false" class="md:w-96">
                <div class="space-y-6">
                    <flux:heading size="lg">Select a Team</flux:heading>
                    <div class="space-y-2">
                        @foreach($this->teams as $team)
                            <flux:button class="w-full" wire:click="switchToTeam('{{ $team->id }}')">
                                {{ $team->name }}
                            </flux:button>
                        @endforeach
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
