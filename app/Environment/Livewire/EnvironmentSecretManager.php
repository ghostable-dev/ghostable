<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\ResolveEnvironmentSecrets;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Organization\Enums\OrganizationPermission;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
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
        $this->dispatch(EnvironmentSecretVersionManager::LAUNCH, $secret->id);
    }

    public function viewDetails(EnvironmentSecret $secret): void
    {
        $this->dispatch(EnvironmentSecretDetailsViewer::LAUNCH, $secret->id);
    }

    public function render()
    {
        return view('environment.environment-secret-manager');
    }
}
