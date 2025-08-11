<?php

namespace App\Environment\Variable\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Environment\Actions\LogEnvironmentDownloaded;
use App\Environment\Actions\LogEnvironmentViewed;
use App\Environment\Actions\RenderEnvFile;
use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Validation\Actions\ValidateEnvironment;
use App\Environment\Variable\Actions\LogVariableRevealed;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Versioning\Livewire\VersionManager;
use App\Team\Enums\TeamPermission;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class VariableManager extends Component
{
    use ConfirmsPasswords;

    /**
     * The ID of the environment currently being managed.
     */
    #[Locked]
    public string $environmentId;

    /**
     * Default sort key
     */
    public string $sortBy = 'key';

    /**
     * Table sort direction
     */
    public string $sortDirection = 'asc';

    #[Locked]
    public array $showing = [];

    public function mount(Environment $environment): void
    {
        $this->authorize('perform', [$environment, TeamPermission::ViewVariables]);

        $this->forcePasswordConfirmation();

        $this->environmentId = $environment->id;

        app(LogEnvironmentViewed::class)->handle(
            environment: $environment,
            user: Auth::user(),
            source: 'ui',
        );
    }

    #[Computed]
    public function validationErrors(): MessageBag
    {
        try {
            // Run validator but catch failures instead of throwing
            app(ValidateEnvironment::class)->handle($this->environment);

            return new MessageBag; // No errors
        } catch (\Illuminate\Validation\ValidationException $e) {
            return new MessageBag($e->errors());
        }
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
     * Determine if the authenticated user can edit variables
     * inside of the given environment.
     */
    #[Computed(persist: true)]
    public function canEditVariables(): bool
    {
        return Gate::allows('perform', [$this->environment, TeamPermission::EditVariables]);
    }

    /**
     * Retrieve the list of environment variables for the current environment,
     * sorted by the currently selected column and direction.
     *
     * Sorting is controlled via `$sortBy` and `$sortDirection` properties.
     *
     * @return Collection<int, EnvironmentVariable>
     */
    #[Computed]
    public function variables(): Collection
    {
        $vars = resolve(ResolveEnvironmentVariables::class)
            ->handle($this->environment);

        if ($this->sortBy === 'key') {
            return $this->sortDirection === 'desc'
                ? $vars->sortByDesc('key')
                : $vars->sortBy('key');
        } elseif ($this->sortBy === 'last_updated_at') {
            return $this->sortDirection === 'desc'
                ? $vars->sortByDesc('last_updated_at')
                : $vars->sortBy('last_updated_at');
        }

        return $vars;
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
        if (! in_array($column, ['key', 'last_updated_at'], true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Dispatch an event to open the environment
     * variable editor for the given variable.
     */
    public function editVariable(EnvironmentVariable $var): void
    {
        $this->launchVariableModal(VariableEditor::class, $var);
    }

    /**
     * Dispatch an event to open the environment
     * variable deleter for the given variable.
     */
    public function removeVariable(EnvironmentVariable $var): void
    {
        $this->launchVariableModal(VariableDeleter::class, $var);
    }

    /**
     * Dispatch an event to open the environment
     * variable reinstater for the given variable.
     */
    public function reinstateVariable(EnvironmentVariable $var): void
    {
        $this->launchVariableModal(VariableReinstater::class, $var);
    }

    /**
     * Dispatch an event to open the given variable modal.
     */
    public function launchVariableModal(string $modalClass, EnvironmentVariable $var): void
    {
        if (! class_exists($modalClass)) {
            throw new Exception(sprintf('Class %s does not exist.', $modalClass));
        }

        if (! is_subclass_of($modalClass, VariableModalComponent::class)) {
            throw new Exception(sprintf(
                '%s must extend %s.',
                $modalClass,
                VariableModalComponent::class
            ));
        }

        $this->dispatch(
            $modalClass::LAUNCH,
            variable: $var->id,
            targetEnvironment: $this->environment->id
        );
    }

    /**
     * Dispatch an event to open the environment
     * variable activity feed for the given variable.
     */
    public function viewVariableActivity(EnvironmentVariable $variable): void
    {
        $this->dispatch(VariableActivityFeed::LAUNCH, $variable->id);
    }

    /**
     * Dispatch an event to open the versions
     * manager for the given variable.
     */
    public function viewVersions(EnvironmentVariable $variable): void
    {
        $this->dispatch(VersionManager::LAUNCH, $variable->id);
    }

    /**
     * Download the full environment file.
     */
    public function downloadEnvFile()
    {
        $this->authorize('perform', [$this->environment, TeamPermission::ViewVariables]);

        $content = RenderEnvFile::handle(env: $this->environment);

        app(LogEnvironmentDownloaded::class)->handle(
            environment: $this->environment,
            user: Auth::user(),
            source: 'ui',
        );

        $filename = 'environment-'.str($this->environment->name)->slug().'.env';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename);
    }

    /**
     * Livewire listener to refresh the list of environment variables
     * after a variable has been updated via the editor.
     *
     * This is triggered by the `VariableEditor::UPDATED` event.
     */
    #[On([
        VariableEditor::UPDATED,
        VariableCreator::CREATED,
        VariableDeleter::DELETED,
        VariableReinstater::REINSTATED,
        VersionManager::VERSION_RESTORED,
    ])]
    public function refreshVars(): void
    {
        // $this->dispatch('$refresh');
        $this->validationErrors();
        $this->variables();

        $this->environment->refresh();
    }

    public function toggleSecret(EnvironmentVariable $var): void
    {
        $this->authorize('perform', [$var->environment, TeamPermission::EditVariables]);

        $isNowVisible = ! ($this->showing[$var->id] ?? false);

        $this->showing[$var->id] = $isNowVisible;

        if ($isNowVisible) {
            app(LogVariableRevealed::class)->handle($var);
            $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        }
    }

    public function render()
    {
        return view('environment.variable.variable-manager');
    }
}
