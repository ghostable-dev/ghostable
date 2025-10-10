<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\ResolveEnvironmentSecrets;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Variable\Actions\LogVariableRevealed;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Versioning\Livewire\VersionManager;
use App\Organization\Enums\OrganizationPermission;
use Exception;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class EnvironmentSecretManager extends Component
{
    /**
     * The ID of the environment currently being managed.
     */
    #[Locked]
    public string $environmentId;

    /**
     * Default sort name
     */
    public string $sortBy = 'name';

    /**
     * Table sort direction
     */
    public string $sortDirection = 'asc';

    public function mount(Environment $environment): void
    {
        $this->authorize('perform', [$environment, OrganizationPermission::ViewVariables]);

        $this->environmentId = $environment->id;
    }

    /**
     * Retrieve the current environment instance based on the provided environment ID.
     *
     * This method will throw a ModelNotFoundException if the environment does not exist.
     */
    #[Computed]
    public function environment(): Environment
    {
        return ResolveEnvironment::onceWithContext($this->environmentId);
    }

    /**
     * Retrieve the list of environment variables for the current environment,
     * sorted by the currently selected column and direction.
     *
     * Sorting is controlled via `$sortBy` and `$sortDirection` properties.
     */
    #[Computed]
    public function variables(): Collection
    {
        $secrets = resolve(ResolveEnvironmentSecrets::class)
            ->handle($this->environment);

        if ($this->sortBy === 'name') {
            return $this->sortDirection === 'desc'
                ? $secrets->sortByDesc('name')
                : $secrets->sortBy('name');
        } elseif ($this->sortBy === 'last_updated_at') {
            return $this->sortDirection === 'desc'
                ? $secrets->sortByDesc('last_updated_at')
                : $secrets->sortBy('last_updated_at');
        }

        return $secrets;
    }

    /**
     * Update the sorting configuration for environment variables.
     *
     * If the same column is clicked again, the sort
     * direction toggles between ascending and descending.
     * Otherwise, it sets the new column as the sort target
     * and resets direction to ascending.
     */
    public function sort(string $column): void
    {
        if (! in_array($column, ['name', 'last_updated_at'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function viewActivity(EnvironmentSecret $secret): void
    {
        $this->dispatch(EnvironmentSecretActivityFeed::LAUNCH, $secret->id);
    }

    public function viewVersions(EnvironmentSecret $secret): void
    {
        $this->dispatch(VersionManager::LAUNCH, $secret->id);
    }

    public function viewDetails(EnvironmentSecret $secret): void
    {
        $this->dispatch(EnvironmentSecretDetailsViewer::LAUNCH, $secret->id);
    }

    /**
     * Dispatch an event to open the environment
     * variable deleter for the given variable.
     */
    // public function removeVariable(EnvironmentVariable $var): void
    // {
    //     $this->launchVariableModal(VariableDeleter::class, $var);
    // }

    /**
     * Dispatch an event to open the environment
     * variable reinstater for the given variable.
     */
    // public function reinstateVariable(EnvironmentVariable $var): void
    // {
    //     $this->launchVariableModal(VariableReinstater::class, $var);
    // }

    /**
     * Dispatch an event to open the given variable modal.
     */
    // public function launchVariableModal(string $modalClass, EnvironmentVariable $var): void
    // {
    //     if (! class_exists($modalClass)) {
    //         throw new Exception(sprintf('Class %s does not exist.', $modalClass));
    //     }

    //     if (! is_subclass_of($modalClass, VariableModalComponent::class)) {
    //         throw new Exception(sprintf(
    //             '%s must extend %s.',
    //             $modalClass,
    //             VariableModalComponent::class
    //         ));
    //     }

    //     $this->dispatch(
    //         $modalClass::LAUNCH,
    //         variable: $var->id,
    //         targetEnvironment: $this->environment->id
    //     );
    // }

    /**
     * Dispatch an event to open the environment
     * variable activity feed for the given variable.
     */
    // public function viewVariableActivity(EnvironmentVariable $variable): void
    // {
    //     $this->dispatch(VariableActivityFeed::LAUNCH, $variable->id);
    // }

    /**
     * Dispatch an event to open the versions
     * manager for the given variable.
     */
    // @codeCoverageIgnoreStart
    // public function viewVersions(EnvironmentVariable $variable): void
    // {
    //     $this->dispatch(VersionManager::LAUNCH, $variable->id);
    // }
    // @codeCoverageIgnoreEnd

    /**
     * Livewire listener to refresh the list of environment variables
     * after a variable has been updated via the editor.
     *
     * This is triggered by the `VariableEditor::UPDATED` event.
     */
    // #[On([
    //     EnvironmentImporter::IMPORTED,
    //     VariableEditor::UPDATED,
    //     VariableCreator::CREATED,
    //     VariableDeleter::DELETED,
    //     VariableReinstater::REINSTATED,
    //     VersionManager::VERSION_RESTORED,
    // ])]
    // public function refreshVars(): void
    // {
    //     // $this->dispatch('$refresh');
    //     $this->validationErrors();
    //     $this->variables();

    //     $this->environment->refresh();
    // }

    // public function toggleSecret(EnvironmentVariable $var): void
    // {
    //     $this->authorize('perform', [$var->environment, OrganizationPermission::EditVariables]);

    //     $isNowVisible = ! ($this->showing[$var->id] ?? false);

    //     $this->showing[$var->id] = $isNowVisible;

    //     if ($isNowVisible) {
    //         app(LogVariableRevealed::class)->handle($var);
    //         $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
    //     }
    // }

    public function render()
    {
        return view('environment.environment-secret-manager');
    }
}
