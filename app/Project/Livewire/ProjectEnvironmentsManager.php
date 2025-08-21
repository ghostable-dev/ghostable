<?php

namespace App\Project\Livewire;

use App\Environment\Actions\CreateEnv;
use App\Environment\Actions\GenerateSuggestedEnvironmentNames;
use App\Environment\Actions\NormalizeEnvironmentName;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Rules\EnvironmentRules;
use App\Project\Models\Project;
use App\Project\Resolvers\ResolveProject;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
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
     * The base environment.
     */
    public ?string $base_id = '';

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
    public EnvironmentType $type = EnvironmentType::DEVELOPMENT;

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

    #[Computed]
    public function nameSuggestions(): array
    {
        return GenerateSuggestedEnvironmentNames::handle(
            project: $this->project,
            type: $this->type
        );
    }

    public function updatedType()
    {
        $this->name = $this->nameSuggestions[0] ?? '';
    }

    public function updatedName($value)
    {
        $this->name = resolve(NormalizeEnvironmentName::class)->handle($value);
    }

    /**
     * Create a new environment under the current project.
     */
    public function createEnvironment(): void
    {
        $this->authorize('perform', [$this->project, TeamPermission::CreateEnvironments]);

        $validated = $this->validate(EnvironmentRules::createRules($this->project));

        $validated['base_id'] = $validated['base_id'] ?: null;
        $base = $this->project->environments()->where('id', $validated['base_id'])->first();

        try {
            app(CreateEnv::class)->handle(
                name: $this->name,
                type: $this->type,
                project: $this->project,
                base: $base
            );
        } catch (ValidationException $e) {
            if ($e->validator->errors()->has('environment_limit')) {
                Flux::modal('upgrade-environment-limit')->show();
                return;
            }

            throw $e;
        }

        $this->reset('type', 'name');

        Flux::modal('create-env')->close();
        Flux::toast('The new environment has been created.');
    }

    public function render()
    {
        return view('project.project-environments-manager');
    }
}
