<?php

namespace App\Environment\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Environment\Actions\LogEnvironmentDownloaded;
use App\Environment\Actions\LogEnvironmentViewed;
use App\Environment\Actions\RenderEnvFile;
use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Actions\SuggestEnvKeys;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Validation\Actions\ValidateEnvironment;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Actions\GetSuggestedVariableValues;
use App\Environment\Variable\Actions\LogVariableRevealed;
use App\Environment\Variable\Actions\NormalizeVariableKey;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Variable\Registry\VariableRegistry;
use App\Environment\Variable\Rules\VariableRules;
use App\Environment\Versioning\Livewire\VersionManager;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Gate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\MessageBag;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class EnvironmentVariableManager extends Component
{
    use ConfirmsPasswords;

    /**
     * The ID of the environment currently being managed.
     */
    #[Locked]
    public string $envId;

    /**
     * The key of the environment variable being created.
     */
    public string $key = '';

    /**
     * The value of the environment variable being created.
     */
    public string $value = '';

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

        $this->envId = $environment->id;

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
        return ResolveEnvironment::onceWithContext($this->envId);
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
     * Get a list of suggested environment variable keys for the current environment.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function keySuggestions(): array
    {
        return app(SuggestEnvKeys::class)->handle($this->environment);
    }

    /**
     * Get the description for the currently selected environment variable key.
     *
     * Returns null if no key is selected or if the key
     * is not registered in the VariableRegistry.
     */
    #[Computed]
    public function keyDescription(): ?string
    {
        if (! $this->key) {
            return null;
        }

        return app(VariableRegistry::class)->get($this->key)?->description();
    }

    /**
     * Get a list of suggested values for the currently selected environment variable key.
     *
     * Suggestions are provided by the VariableRegistry based on the key's
     * corresponding definition class. Returns an empty array if the key has no suggestions defined.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function valueSuggestions(): array
    {
        return app(GetSuggestedVariableValues::class)->handle($this->key);
    }

    /**
     * Livewire lifecycle hook: triggered when the `key` property is updated.
     *
     * Normalizes the environment variable key by converting it to an uppercase
     * slug-style string using underscores (e.g., "app url" becomes "APP_URL").
     */
    public function updatedKey($value)
    {
        $this->key = app(NormalizeVariableKey::class)->handle($value);

        $this->value = '';
    }

    /**
     * Add a new environment variable to the current environment.
     *
     * This method:
     * - Authorizes the user for the `EditVariables` permission
     * - Validates the key and value input against environment-specific rules
     * - Creates the variable record
     * - Resets the input fields
     * - Shows a success toast notification
     */
    public function addEnvironmentVariable(): void
    {
        $this->authorize('perform', [$this->environment, TeamPermission::EditVariables]);

        $validated = $this->validate(
            rules: VariableRules::create($this->environment),
            attributes: ['key' => $this->key, 'value' => $this->value]
        );

        $variable = app(CreateVariable::class)
            ->handle($this->toCreateVariableData($validated));

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        $this->refreshVars();

        $this->reset('key', 'value');
        Flux::toast("New variable '{$variable->key}' added.");
    }

    /**
     * Transform a raw input array into a CreateVariableData DTO.
     *
     * This helper is used to convert incoming data (e.g., from a request or import)
     * into a structured format suitable for creating an environment variable.
     * It automatically associates the current environment and authenticated user.
     */
    private function toCreateVariableData(array $input): CreateVariableData
    {
        return new CreateVariableData(
            environment: $this->environment,
            key: $input['key'],
            value: $input['value'],
            createdBy: Auth::user()
        );
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
     * variable deleter for the given variable.
     *
     * This triggers the `EnvironmentVariableDeleter`
     * component to load and show the modal.
     */
    public function removeVariable(EnvironmentVariable $var): void
    {
        $this->dispatch(
            EnvironmentVariableDeleter::LAUNCH,
            variable: $var->id,
            targetEnvironment: $this->environment->id
        );
    }

    /**
     * Dispatch an event to open the environment
     * variable editor for the given variable.
     *
     * This triggers the `EnvironmentVariableEditor`
     * component to load and show the modal.
     */
    public function editVariable(EnvironmentVariable $variable): void
    {
        $this->dispatch(
            EnvironmentVariableEditor::LAUNCH,
            variable: $variable->id,
            targetEnvironment: $this->environment->id
        );
    }

    /**
     * Dispatch an event to open the environment
     * variable activity feed for the given variable.
     */
    public function viewVariableActivity(EnvironmentVariable $variable): void
    {
        $this->dispatch(EnvironmentVariableActivityFeed::LAUNCH, $variable->id);
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
     * This is triggered by the `EnvironmentVariableEditor::UPDATED` event.
     */
    #[On([
        EnvironmentVariableEditor::UPDATED,
        VersionManager::VERSION_RESTORED,
    ])]
    public function refreshVars(): void
    {
        // $this->dispatch('$refresh');
        $this->keySuggestions();
        $this->variables();
        $this->validationErrors();

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
        return view('environment.environment-variable-manager');
    }
}
