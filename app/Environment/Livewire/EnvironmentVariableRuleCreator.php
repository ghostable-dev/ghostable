<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\NormalizeEnvKey;
use App\Environment\Actions\SuggestEnvKeys;
use App\Environment\Actions\Validation\CreateRule;
use App\Environment\Enums\EnvironmentVariableRuleType;
use App\Environment\Rules\EnvVariableRules;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class EnvironmentVariableRuleCreator extends EnvironmentComponent
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
    
    public string $key = '';
    
    public bool $is_required = false;
    
    public EnvironmentVariableRuleType $type = EnvironmentVariableRuleType::STRING;
    
    public ?int $min_length = null;
    
    public ?int $max_length = null;
    
    public ?int $min_value = null;
    
    public ?int $max_value = null;
    
    public array $allowed_values = [];
    
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
    
    public function add(): void
    {
        $validated = $this->validate([
            'key'             => EnvVariableRules::keyRules(),
            'is_required'     => 'boolean',
            //'type'            => ['required', Rule::in(EnvironmentVariableRuleType::cases())],
            'min_length'      => 'nullable|integer|min:0',
            'max_length'      => 'nullable|integer|min:0|gte:min_length',
            'min_value'        => 'nullable|integer',
            'max_value'        => 'nullable|integer|gte:min_value',
            'allowed_values'  => 'nullable|array',
            'allowed_values.*'=> 'string',
            'description'     => 'nullable|string',
        ]);

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

        $this->reset([
            'key', 'is_required', 'type',
            'min_length', 'max_length',
            'min_value', 'max_value',
            'allowed_values', 'description',
        ]);

        Flux::toast("New rule for key '{$rule->key}' added.");
        $this->dispatch(self::ADDED, $rule->id);
        $this->showing = false;
    }
    
    public function render()
    {
        return view('environment.environment-variable-rule-creator');
    }
}
