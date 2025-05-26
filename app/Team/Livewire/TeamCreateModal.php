<?php

namespace App\Team\Livewire;

use App\Team\Actions\CreateTeam;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TeamCreateModal extends Component
{
    public string $name = '';
    
    public function create()
    {
        CreateTeam::handle(
            name: $this->name,
            owner: Auth::user()
        );
        
        $this->name = '';
        
        Flux::modal('create-team')->close();
        Flux::toast('New team has been created.');
    }
    
    public function render()
    {
        return <<<'BLADE'
            <flux:modal name="create-team" class="md:w-96">
                <form wire:submit="create" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Create Team</flux:heading>
                        <flux:text class="mt-2"></flux:text>
                    </div>
                    <flux:input label="Name" wire:model="name" required />
                    <div class="flex">
                        <flux:spacer />
                        <flux:button type="submit" variant="primary">Create team</flux:button>
                    </div>
                </form>
            </flux:modal>
        BLADE;
    }
}
