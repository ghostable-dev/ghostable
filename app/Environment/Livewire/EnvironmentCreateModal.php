<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\CreateEnv;
use App\Environment\Enums\EnvironmentType;
use App\Project\Models\Project;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EnvironmentCreateModal extends Component
{
    public string $name = '';
    public string $type = '';
    
    #[Locked]
    public string $projectId;

    public function mount(Project $project): void
    {
        $this->projectId = $project->id;
        $this->type = EnvironmentType::PRODUCTION->value;
    }
    
    public function create()
    {
        app(CreateEnv::class)->handle(
            name: $this->name,
            type: EnvironmentType::from($this->type),
            project: $this->project
        );

        $this->name = '';
        $this->type = EnvironmentType::PRODUCTION->value;
        
        Flux::modal('create-env')->close();
        Flux::toast('New environment has been created.');
    }
    
    #[Computed()]
    public function project(): Project
    {
        return Project::findOrFail($this->projectId);
    }
    
    public function render()
    {
        return <<<'BLADE'
            <flux:modal name="create-env" class="md:w-96">
                <form wire:submit="create" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Create Environment</flux:heading>
                        <flux:text class="mt-2"></flux:text>
                    </div>
                    <flux:input label="Name" wire:model="name" required />
                    <flux:select label="Type" wire:model="type" :options="\App\Environment\Enums\EnvironmentType::selectOptions()" />
                    <div class="flex">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">Create environment</flux:button>
                    </div>
                </form>
            </flux:modal>
        BLADE;
    }
}
