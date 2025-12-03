<?php

namespace App\Project\Livewire;

use App\Organization\Models\Organization;
use App\Project\Actions\CreateProject;
use App\Project\Entities\CreateProjectPayload;
use App\Project\Entities\ProjectStackData;
use App\Project\Enums\DeploymentProvider;
use App\Project\Models\Project;
use App\Project\Support\ProjectStackOptions;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProjectCreateModal extends Component
{
    public string $name = '';

    public DeploymentProvider $deploymentProvider = DeploymentProvider::OTHER;

    public ?string $language = null;

    public ?string $framework = null;

    public ?string $platform = null;

    public bool $withDefaultEnvironments = true;

    #[Computed]
    public function languageOptions(): array
    {
        return ProjectStackOptions::languageOptions();
    }

    #[Computed]
    public function frameworkOptions(): array
    {
        if ($this->language === null) {
            return [];
        }

        return ProjectStackOptions::frameworksFor($this->language);
    }

    #[Computed]
    public function platformOptions(): array
    {
        if ($this->language === null) {
            return [];
        }

        return ProjectStackOptions::platformsFor($this->language);
    }

    public function updatedLanguage(?string $value = null): void
    {
        $this->framework = null;
        $this->platform = null;
        $this->deploymentProvider = DeploymentProvider::OTHER;
    }

    public function updatedFramework(?string $value = null): void
    {
        $this->platform = null;
        $this->deploymentProvider = DeploymentProvider::OTHER;
    }

    public function updatedPlatform(?string $value): void
    {
        $this->deploymentProvider = ProjectStackOptions::providerForPlatform($value);
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
                    withDefaultEnvironments: $this->withDefaultEnvironments,
                    stack: $this->stackPayload()
                )
            );
        } catch (ValidationException $e) {
            if ($e->validator->errors()->has('project_limit')) {
                Flux::modal('upgrade-project-limit')->show();

                return;
            }

            throw $e;
        }

        $this->reset('name', 'language', 'framework', 'platform');

        $this->dispatch('project-created');

        Flux::modal('create-project')->close();
        Flux::toast('New project has been created.');
    }

    protected function stackPayload(): ?ProjectStackData
    {
        if (! $this->language && ! $this->framework && ! $this->platform) {
            return null;
        }

        return ProjectStackOptions::stackData($this->language, $this->framework, $this->platform);
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
                            label="Language" 
                            wire:model.live="language" 
                            placeholder="Select language..."
                            description:trailing="We’ll tailor recommendations based on your stack."
                            required>
                            @foreach($this->languageOptions as $option)
                                <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        @if($this->language)
                            <flux:select 
                                variant="listbox" 
                                label="Framework" 
                                wire:model.live="framework" 
                                placeholder="Select framework..."
                                description:trailing="Choose the framework or runtime that best matches your project."
                                required>
                                @foreach($this->frameworkOptions as $option)
                                    <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif

                        @if($this->framework)
                            <flux:select 
                                variant="listbox" 
                                label="Provider" 
                                wire:model.live="platform" 
                                placeholder="Select provider..."
                                description:trailing="Tell us where this project is running so we can enable the right integrations."
                                required>
                                @foreach($this->platformOptions as $option)
                                    <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @endif
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
