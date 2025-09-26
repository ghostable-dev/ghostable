<?php

namespace App\Project\Livewire;

use App\Organization\Models\Organization;
use App\Project\Actions\CreateProject;
use App\Project\Entities\CreateProjectPayload;
use App\Project\Enums\DeploymentProvider;
use App\Project\Models\Project;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProjectCreateModal extends Component
{
    public string $name = '';

    public DeploymentProvider $deploymentProvider = DeploymentProvider::OTHER;

    public bool $withDefaultEnvironments = true;

    #[Computed(persist: true)]
    public function deploymentProviders(): array
    {
        return DeploymentProvider::cases();
    }

    public function create()
    {
        $this->authorize('create', [Project::class, $this->organization]);

        try {
            resolve(CreateProject::class)->handle(
                new CreateProjectPayload(
                    name: $this->name,
                    organization: $this->organization,
                    deploymentProvider: $this->deploymentProvider,
                    withDefaultEnvironments: $this->withDefaultEnvironments
                )
            );
        } catch (ValidationException $e) {
            if ($e->validator->errors()->has('project_limit')) {
                Flux::modal('upgrade-project-limit')->show();

                return;
            }

            throw $e;
        }

        $this->reset('name');

        $this->dispatch('project-created');

        Flux::modal('create-project')->close();
        Flux::toast('New project has been created.');
    }

    #[Computed(persist: true)]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <flux:modal name="create-project" class="md:w-lg">
                    <form wire:submit="create" class="space-y-6">
                        <div>
                            <flux:heading size="lg">Create Project</flux:heading>
                            <flux:text class="mt-2"></flux:text>
                        </div>
                        <flux:input label="Name" wire:model="name" required />
                        <flux:switch 
                            label="Create default environments?" 
                            wire:model="withDefaultEnvironments" 
                            description="Automatically set up common environments (production, staging, development, local) to get your project started quickly."
                            required />
                        <flux:select 
                            variant="listbox" 
                            label="Deployment Provider" 
                            wire:model="deploymentProvider" 
                            description:trailing="This helps Ghostable enable provider-specific controls and integrations for your project."
                            required>
                            @foreach($this->deploymentProviders as $provider)
                                <flux:select.option value="{{ $provider->value }}">{{ $provider->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="primary">Create project</flux:button>
                        </div>
                    </form>
                </flux:modal>

                <flux:modal name="upgrade-project-limit" class="md:w-96">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">Upgrade Required</flux:heading>
                            <flux:text class="mt-2">Project limit reached for this organization. Upgrade to create more projects.</flux:text>
                        </div>
                        <div class="flex justify-end">
                            <flux:button variant="primary">Upgrade Plan</flux:button>
                        </div>
                    </div>
                </flux:modal>
            </div>
        BLADE;
    }
}
