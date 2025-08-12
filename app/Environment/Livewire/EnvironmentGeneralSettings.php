<?php

namespace App\Environment\Livewire;

use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\EnvironmentAncestryResolver;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Rules\EnvironmentRules;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EnvironmentGeneralSettings extends Component
{
    #[Locked]
    public string $environmentId;

    /**
     * The editable name of the environment.
     */
    public string $name;

    /**
     * The selected environment type (e.g., production, staging).
     */
    public EnvironmentType $type;

    /**
     * Preferred .env file formatting style.
     */
    public EnvFileFormat $fileFormat;

    /**
     * The ID of the base environment this environment derives from.
     */
    public ?string $base_id = null;

    public function mount(Environment $environment): void
    {
        $this->environmentId = $environment->id;

        $this->authorize('view', $environment);

        $this->name = $environment->name;

        $this->type = $environment->type;
        $this->fileFormat = $environment->file_format;
        $this->base_id = $environment->base_id;
    }

    /**
     * Retrieve the current environment instance based on the bound environment ID.
     */
    #[Computed]
    public function environment(): Environment
    {
        return ResolveEnvironment::onceWithContext($this->environmentId);
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

    #[Computed(persist: true)]
    public function formatOptions(): array
    {
        return EnvFileFormat::selectOptions();
    }

    #[Computed]
    public function baseOptions(): Collection
    {
        return $this->environment->project->environments
            ->reject(fn (Environment $env) => $env->id === $this->environment->id || $env->isDescendantOf($this->environment));
    }

    /**
     * Determine whether the authenticated user can manage the current environment's settings.
     *
     * This is based on the manageSettings policy and is persisted for efficient UI checks.
     */
    #[Computed(persist: true)]
    public function canEdit(): bool
    {
        return Gate::allows('manageSettings', $this->environment);
    }

    /**
     * Update the environment's metadata, including its name and type.
     *
     * Authorizes the user with the manageSettings policy and validates input
     * before applying updates. Emits an 'environment-updated' event on success.
     */
    public function updateEnvironment(bool $confirmed = false): void
    {
        $this->authorize('manageSettings', $this->environment);

        $validated = $this->validate(EnvironmentRules::updateRules($this->environment));
        $validated['base_id'] = $validated['base_id'] ?? null;

        if (! $confirmed && $validated['base_id'] !== $this->environment->base_id) {
            Flux::modal('confirm-base-change')->open();
            return;
        }

        $base = $this->environment->project->environments()->where('id', $validated['base_id'])->first();
        if ($base && $base->isDescendantOf($this->environment)) {
            $this->addError('base_id', 'The selected base environment is invalid.');
            return;
        }

        $this->environment->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'file_format' => $validated['fileFormat'],
            'base_id' => $validated['base_id'],
        ]);

        resolve(EnvironmentAncestryResolver::class)->bust($this->environment);

        $this->dispatch('environment-updated');
    }

    public function confirmBaseChange(): void
    {
        $this->updateEnvironment(true);
        Flux::modal('confirm-base-change')->close();
    }

    /**
     * Permanently delete the current environment.
     *
     * This method:
     * - Authorizes the user using the environment-level 'delete' policy
     * - Deletes the environment variables, and overrides
     * - Redirects the user to the project dashboard after deletion
     */
    public function deleteEnvironment(): void
    {
        $project = $this->environment->project;

        $this->authorize('perform', [$project, TeamPermission::DeleteEnvironments]);

        $this->environment->delete();

        $this->redirect(route('projects.view', $project));
    }

    public function render()
    {
        return view('environment.environment-general-settings');
    }
}
