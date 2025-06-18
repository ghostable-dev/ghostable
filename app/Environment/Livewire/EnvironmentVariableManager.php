<?php

namespace App\Environment\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Environment\Actions\CreateEnvVariable;
use App\Environment\Actions\DeleteEnvVariable;
use App\Environment\Actions\GetSuggestedEnvValues;
use App\Environment\Actions\LogEnvironmentViewed;
use App\Environment\Actions\LogVariableRevealed;
use App\Environment\Actions\NormalizeEnvKey;
use App\Environment\Actions\SuggestEnvKeys;
use App\Environment\Entities\CreateEnvVariableData;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use App\Environment\Registry\EnvironmentVariableRegistry;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Rules\EnvVariableRules;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Gate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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

    /**
     * The ID of the variable currently selected for removal.
     *
     * Used to resolve the environment variable
     * instance prior to deletion.
     */
    public ?string $variableToRemoveId = null;

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
     * Returns null if no key is selected or if the key is not registered
     * in the EnvironmentVariableRegistry.
     */
    #[Computed]
    public function keyDescription(): ?string
    {
        if (!$this->key) {
            return null;
        }
        
        return app(EnvironmentVariableRegistry::class)
            ->get($this->key)
            ->description();
    }

    /**
     * Get a list of suggested values for the currently selected environment variable key.
     *
     * Suggestions are provided by the EnvironmentVariableRegistry based on the key's
     * corresponding definition class. Returns an empty array if the key has no suggestions defined.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function valueSuggestions(): array
    {
        return app(GetSuggestedEnvValues::class)->handle($this->key);
    }

    /**
     * Livewire lifecycle hook: triggered when the `key` property is updated.
     *
     * Normalizes the environment variable key by converting it to an uppercase
     * slug-style string using underscores (e.g., "app url" becomes "APP_URL").
     */
    public function updatedKey($value)
    {
        $this->key = app(NormalizeEnvKey::class)->handle($value);
        
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
            rules: EnvVariableRules::create($this->environment),
            attributes: ['key' => $this->key, 'value' => $this->value]
        );

        $variable = app(CreateEnvVariable::class)
            ->handle($this->toCreateVariableData($validated));

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);

        $this->reset('key', 'value');
        Flux::toast("New variable '{$variable->key}' added.");
    }

    /**
     * Transform a raw input array into a CreateEnvVariableData DTO.
     *
     * This helper is used to convert incoming data (e.g., from a request or import)
     * into a structured format suitable for creating an environment variable.
     * It automatically associates the current environment and authenticated user.
     */
    private function toCreateVariableData(array $input): CreateEnvVariableData
    {
        return new CreateEnvVariableData(
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
        return $this->environment->variables()
            ->with('latestVersion')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
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
        if (! in_array($column, ['key', 'updated_at'], true)) {
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
     * Begin the variable removal process by storing the variable ID
     * and showing the confirmation modal.
     *
     * This method checks that the authenticated user has edit access
     * to the variable before proceeding.
     */
    public function confirmVariableRemoval(EnvironmentVariable $var): void
    {
        $this->variableToRemoveId = $var->id;

        $this->authorize('perform', [$var->environment, TeamPermission::EditVariables]);

        Flux::modal('confirm-variable-removal')->show();
    }

    /**
     * Resolve the variable that is pending removal.
     *
     * Returns the variable based on the stored ID, or null if not set.
     */
    #[Computed]
    public function variableToRemove(): ?EnvironmentVariable
    {
        return $this->environment->variables()
            ->firstWhere('id', $this->variableToRemoveId);
    }

    /**
     * Permanently delete the selected variable.
     *
     * Authorization is checked against the variable.
     * After deletion, the confirmation modal is closed.
     */
    public function removeVariable(): void
    {
        $environment = $this->variableToRemove->environment;

        $this->authorize('perform', [$environment, TeamPermission::EditVariables]);

        app(DeleteEnvVariable::class)->handle(
            var: $this->variableToRemove,
            deletedBy: Auth::user()
        );

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);

        Flux::modal('confirm-variable-removal')->close();
        Flux::toast("Variable '{$this->variableToRemove->key}' remove.");

        $this->reset('variableToRemoveId');
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
        $this->dispatch(EnvironmentVariableEditor::LAUNCH, $variable->id);
    }

    /**
     * Dispatch an event to open the environment
     * variable activity feed for the given variable.
     *
     * This triggers the `EnvironmentVariableActivityFeed`
     * component to load and show the modal.
     */
    public function viewVariableActivity(EnvironmentVariable $variable): void
    {
        $this->dispatch(EnvironmentVariableActivityFeed::LAUNCH, $variable->id);
    }

    /**
     * Livewire listener to refresh the list of environment variables
     * after a variable has been updated via the editor.
     *
     * This is triggered by the `EnvironmentVariableEditor::UPDATED` event.
     */
    #[On(EnvironmentVariableEditor::UPDATED)]
    public function refreshVars(): void
    {
        $this->variables();
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
