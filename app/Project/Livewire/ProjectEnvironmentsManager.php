<?php

namespace App\Project\Livewire;

use App\Environment\Actions\CreateEnv;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Rules\EnvironmentRules;
use App\Project\Models\Project;
use App\Project\Resolvers\ResolveProject;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportPagination\HandlesPagination;

class ProjectEnvironmentsManager extends Component
{
    use HandlesPagination;

    #[Locked]
    public string $projectId;

    /**
     * The name of the new environment.
     */
    public string $name = '';

    /**
     * The default environment display tab.
     */
    public string $tab = 'board';

    /**
     * The selected environment type (e.g., Production, Staging).
     */
    public EnvironmentType $type = EnvironmentType::STAGING;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->projectId = $project->id;
    }

    #[Computed]
    public function project(): Project
    {
        return ResolveProject::onceWithContext($this->projectId);
    }

    #[Computed]
    public function environments(): Collection
    {
        return $this->project->environments;
    }

    /**
     * Get the list of available environment types as select options.
     *
     * @return array<string, string>
     */
    #[Computed(persist: true)]
    public function typeOptions(): array
    {
        return EnvironmentType::selectOptions();
    }

    /**
     * Create a new environment under the current project.
     */
    public function createEnvironment(): void
    {
        $this->authorize('perform', [$this->project, TeamPermission::CreateEnvironments]);

        $this->validate(EnvironmentRules::createRules($this->project));

        app(CreateEnv::class)->handle(
            name: $this->name,
            type: $this->type,
            project: $this->project
        );

        $this->reset('type', 'name');

        Flux::modal('create-env')->close();
        Flux::toast('The new environment has been created.');
    }

    public function render()
    {
        return view('project.project-environments-manager');
    }
}
