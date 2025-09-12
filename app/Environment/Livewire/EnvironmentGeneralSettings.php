<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\UpdateBaseEnvironment;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\EnvironmentAncestryResolver;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Rules\EnvironmentRules;
use App\Organization\Enums\OrganizationPermission;
use Flux\Flux;
use Illuminate\Support\Collection;
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
     * Get the list of valid environments that can be selected
     * as the base (parent) for the current environment.
     *
     * Excludes:
     * - The current environment itself.
     * - Any descendant environments (to prevent inheritance cycles).
     *
     * @return Collection<int, Environment>
     */
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
     * Livewire lifecycle hook triggered when the `base_id` property changes.
     *
     * If the selected base environment ID differs from the current
     * environment's existing base ID, show a confirmation modal
     * to ensure the user intends to change the inheritance.
     *
     * This is used to guard against accidental base environment
     * changes, since such changes may affect variables in all
     * derived environments.
     */
    public function updatedBaseId(): void
    {
        if (! $this->base_id !== $this->environment->base_id) {
            Flux::modal('confirm-base-change')->show();

            return;
        }
    }

    /**
     * Handle updating the base (parent) environment for the current environment.
     *
     * Workflow:
     * - Validate the incoming `base_id` using the update base rules.
     * - Retrieve the selected base environment from the same project.
     * - Authorize the action via the `updateBase` policy method.
     * - Perform the base change via the `UpdateBaseEnvironment` action.
     * - Bust the environment ancestry cache so inheritance relationships are recalculated.
     * - Redirect back to the general settings page for the environment.
     */
    public function updateBaseEnvironment(): void
    {
        $validated = $this->validate(EnvironmentRules::updateBaseRules($this->environment));

        $base = $this->environment->project->environments()
            ->where('id', $validated['base_id'] ?? null)
            ->first();

        $this->authorize('updateBase', [$this->environment, $base]);

        resolve(UpdateBaseEnvironment::class)->handle($this->environment, base: $base);

        resolve(EnvironmentAncestryResolver::class)->bust($this->environment);

        $this->redirect(route('environment.settings.general', $this->environment));
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

        // Capture derived environments before deleting so we can detach them.
        $derived = $this->environment->derived()->get();

        // Detach each derived environment from this base and clear ancestry cache.
        foreach ($derived as $env) {
            resolve(UpdateBaseEnvironment::class)->handle($env, base: null);
            resolve(EnvironmentAncestryResolver::class)->bust($env);
        }

        $this->environment->delete();

        $this->redirect(route('project.environments', $project));
    }

    public function render()
    {
        return view('environment.environment-general-settings');
    }
}
