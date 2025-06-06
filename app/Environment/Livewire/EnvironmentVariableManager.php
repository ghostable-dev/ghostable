<?php

namespace App\Environment\Livewire;

use App\Auth\Concerns\ConfirmsPasswords;
use App\Environment\Actions\NormalizeEnvKey;
use App\Environment\Actions\SuggestEnvKeys;
use App\Environment\Enums\CommonEnvKey;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use App\Environment\Rules\EnvVariableRules;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Collection;
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
    }

    /**
     * Retrieve the current environment instance based on the provided environment ID.
     *
     * This method will throw a ModelNotFoundException if the environment does not exist.
     */
    #[Computed]
    public function environment(): Environment
    {
        return Environment::findOrFail($this->envId);
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
     * Get a list of suggested values for the currently selected environment variable key.
     *
     * Suggestions are provided based on the key, using the CommonEnvKey enum logic.
     * Returns an empty array if the key has no predefined suggestions.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function valueSuggestions(): array
    {
        return CommonEnvKey::suggestedValuesFor($this->key);
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

        $variable = $this->environment->variables()->create([
            'key' => $validated['key'],
            'value' => $validated['value'],
        ]);

        $this->reset('key', 'value');
        Flux::toast("New variable '{$variable->key}' added.");
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
        $this->authorize('perform', [$this->variableToRemove->environment, TeamPermission::EditVariables]);

        $this->variableToRemove->delete();

        Flux::modal('confirm-variable-removal')->close();
        Flux::toast("Variable '{$this->variableToRemove->key}' remove.");

        $this->reset('variableToRemoveId');
    }

    /**
     * Dispatch an event to open the environment variable editor for the given variable.
     *
     * This triggers the `EnvironmentVariableEditor` component to load and show the modal.
     */
    public function editVariable(EnvironmentVariable $variable): void
    {
        $this->dispatch(EnvironmentVariableEditor::LAUNCH, $variable->id);
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

        $this->showing[$var->id] = ! ($this->showing[$var->id] ?? false);
    }

    public function render()
    {
        return view('environment.environment-variable-manager');
    }
}
