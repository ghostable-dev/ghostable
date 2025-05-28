<?php

namespace App\Project\Livewire;

use App\Project\Actions\CreateProject;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProjectCreateModal extends Component
{
    public string $name = '';

    public function create()
    {
        app(CreateProject::class)->handle(
            name: $this->name,
            team: Auth::user()->currentTeam()
        );

        $this->reset('name');

        Flux::modal('create-project')->close();
        Flux::toast('New project has been created.');
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal name="create-project" class="md:w-96">
                <form wire:submit="create" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Create Project</flux:heading>
                        <flux:text class="mt-2"></flux:text>
                    </div>
                    <flux:input label="Name" wire:model="name" required />
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Create project</flux:button>
                    </div>
                </form>
            </flux:modal>
        BLADE;
    }
}
