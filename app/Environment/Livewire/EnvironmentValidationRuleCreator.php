<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\NormalizeEnvKey;
use App\Environment\Actions\SuggestEnvKeys;
use App\Environment\Actions\Validation\CreateRule;
use App\Environment\Enums\EnvironmentVariableRuleType;
use App\Environment\Rules\VariableValidationRules;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class EnvironmentValidationRuleCreator extends EnvironmentComponent
{
    /**
     * Indicates whether the variable editor modal is currently visible.
     */
    public bool $showing = false;
    
    /**
     * Event name used to trigger the variable 
     * rule creator modal.
     */
    public const LAUNCH = 'variable-rule-creator:launch';
    
    /**
     * Event name used to trigger the variable rule 
     * creator modal added a variable.
     */
    public const ADDED = 'variable-rule-creator:added';
    
    /**
     * The unique identifier for the environment variable.
     */
    public string $key = '';                 
    
    /**
     * Whether this environment variable is required.
     */
    public bool $is_required = false;
    
    /**
     * The type of the environment variable, represented as an enum.
     */
    public EnvironmentVariableRuleType $type = EnvironmentVariableRuleType::STRING;
    
    /**
     * The minimum allowed length for string values.
     */
    public ?int $min_length = null;
    
    /**
     * The maximum allowed length for string values.
     */
    public ?int $max_length = null;
    
    /**
     * The minimum allowed numeric value.
     */
    public ?int $min_value = null;
    
    /**
     * The maximum allowed numeric value.
     */
    public ?int $max_value = null;
    
    /**
     * A list of explicitly permitted values (strings).
     *
     * @var string[]
     */
    public array $allowed_values = [];
    
    /**
     * An optional human-readable description of the rule.
     */
    public ?string $description = null;
    
    /**
     * Livewire event listener to launch the environment rule creator modal.
     */
    #[On(self::LAUNCH)]
    public function launch(): void
    {
        $this->showing = true;
    }
    
    /**
     * Get a list of suggested environment variable 
     * validation keys for the current environment.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function keySuggestions(): array
    {
        return app(SuggestEnvKeys::class)->handle($this->environment);
    }
    
    /**
     * Get the available rule types for environment variable validation.
     *
     * @return EnvironmentVariableRuleType[] Array of all defined rule types.
     */
    #[Computed]
    public function ruleTypeOptions(): array
    {
        return EnvironmentVariableRuleType::cases();
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
     * Validate input and create a new environment variable rule.
     *
     * Performs the following steps:
     * 1. Validates the component’s properties against the defined rules.
     * 2. Invokes the CreateRule action to persist the new rule.
     * 3. Resets the Livewire properties to their defaults.
     * 4. Displays a success toast message.
     * 5. Dispatches the `ADDED` event with the new rule’s ID.
     * 6. Closes the “add rule” form/modal.
     */
    public function add(): void
    {
        $rules = VariableValidationRules::createRules();
        
        $validated = $this->validate($rules);
        
        $rule = app(CreateRule::class)->handle(
            environment:    $this->environment,
            key:            $validated['key'],
            isRequired:     $validated['is_required'],
            type:           $this->type,
            settings: [
                'min_length'     => $validated['min_length'] ?? null,
                'max_length'     => $validated['max_length'] ?? null,
                'min_value'      => $validated['min_value'] ?? null,
                'max_value'      => $validated['max_value'] ?? null,
                'allowed_values' => $validated['allowed_values'] ?? [],
            ],
            description:    $validated['description'] ?? null,
        );

        $this->resetForm();

        Flux::toast("New rule for key '{$rule->key}' added.");
        $this->dispatch(self::ADDED, $rule->id);
        $this->showing = false;
    }
    
    /**
     * Reset the form properties to their default state.
     *
     * This will clear out all the input fields used when creating
     * or editing an environment variable rule.
     */
    protected function resetForm(): void
    {
        $this->reset([
            'key', 
            'is_required', 
            'type',
            'min_length', 
            'max_length',
            'min_value', 
            'max_value',
            'allowed_values', 
            'description',
        ]);
    }
    
    public function render()
    {
        return view('environment.environment-validation-rule-creator');
    }
}
