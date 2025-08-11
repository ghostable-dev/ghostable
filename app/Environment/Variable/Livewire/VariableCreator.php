<?php

namespace App\Environment\Variable\Livewire;

use App\Environment\Actions\SuggestEnvKeys;
use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironment;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Actions\GetSuggestedVariableValues;
use App\Environment\Variable\Actions\NormalizeVariableKey;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Environment\Variable\Registry\VariableRegistry;
use App\Environment\Variable\Rules\VariableRules;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class VariableCreator extends Component
{
    /**
     * Events
     */
    public const CREATED = 'variable-creator:created';
    
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

    public function mount(Environment $environment): void
    {
        $this->authorize('perform', [$environment, TeamPermission::EditVariables]);

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
        return ResolveEnvironment::onceWithContext($this->envId);
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
    public function addVariable(): void
    {
        $this->authorize('perform', [$this->environment, TeamPermission::EditVariables]);

        $validated = $this->validate(
            rules: VariableRules::create($this->environment),
            attributes: ['key' => $this->key, 'value' => $this->value]
        );

        $variable = app(CreateVariable::class)
            ->handle($this->toCreateVariableData($validated));

        $this->dispatch(self::CREATED);
        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);

        $this->reset('key', 'value');
        $this->refresh();
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
    
    #[On(VariableDeleter::DELETED)]
    public function refresh(): void
    {
        $this->environment->refresh();
        $this->keySuggestions();
    }

    public function render()
    {
        return <<<'BLADE'
            <div>
                <form wire:submit="addVariable" class="flex flex-inline items-end gap-4">
                    <div class="basis-1/2 grow-0">
                        <x-environment-key-autocomplete
                            wire:model.live="key" 
                            label="Key" 
                            placeholder="e.g. PARANORMAL_STATUS"
                            required
                            :groupedSuggestions="$this->keySuggestions"/>
                    </div>
                    <div class="basis-1/2 grow-0">
                        <flux:autocomplete 
                            wire:model.live="value" 
                            label="Value" 
                            placeholder="{{ empty($this->key) ? 'we_got_one' : '' }}"
                            required>
                            @foreach($this->valueSuggestions as $suggestion)
                                <flux:autocomplete.item wire:key="value-{{ $suggestion }}">
                                    {{ $suggestion }}
                                </flux:autocomplete.item>
                            @endforeach
                        </flux:autocomplete>
                    </div>
                    <div class="flex-none">
                        <flux:button 
                            type="submit" 
                            variant="primary" 
                            icon:trailing="plus">
                            Add Variable
                        </flux:button>
                    </div>
                </form>
                <flux:text variant="subtle" class="mt-4 flex flex-inline gap-1">
                    @if($this->keyDescription)
                        <flux:icon.information-circle variant="mini"/>
                        <span>{{ $this->keyDescription }}</span>
                    @else
                        Define a new key-value pair in this environment.
                    @endif
                </flux:text>
            </div>
        BLADE;
    }
}
