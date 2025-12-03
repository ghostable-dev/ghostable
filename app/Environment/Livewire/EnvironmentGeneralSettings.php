<?php

namespace App\Environment\Livewire;

use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Rules\EnvironmentRules;
use App\Organization\Enums\OrganizationPermission;
use Illuminate\Support\Facades\Gate;
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

    public function mount(Environment $environment): void
    {
        $this->environmentId = $environment->id;

        $this->authorize('view', $environment);

        $this->name = $environment->name;

        $this->type = $environment->type;
        $this->fileFormat = $environment->file_format;
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

    /**
     * Get the available environment file format options for selection.
     *
     * This is a computed, persistent property so that the options
     * remain cached between Livewire component requests.
     *
     * @return array<int, string>
     */
    #[Computed(persist: true)]
    public function formatOptions(): array
    {
        return EnvFileFormat::selectOptions();
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
    public function updateEnvironment(): void
    {
        $this->authorize('manageSettings', $this->environment);

        $validated = $this->validate(EnvironmentRules::updateRules($this->environment));

        $this->environment->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'file_format' => $validated['fileFormat'],
        ]);
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

        $this->authorize('perform', [$project, OrganizationPermission::DeleteEnvironments]);

        $this->environment->delete();

        $this->redirect(route('project.environments', $project));
    }

    public function render()
    {
        return view('environment.environment-general-settings');
    }
}
