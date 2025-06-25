<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\NormalizeEnvKey;
use App\Environment\Actions\SuggestEnvKeys;
use App\Environment\Actions\Validation\CreateRule;
use App\Environment\Enums\EnvironmentVariableRuleType;
use App\Environment\Rules\EnvVariableRules;
use Flux\Flux;
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
        //$this->authorize('perform', [$this->environment, TeamPermission::EditVariables]);
        
        $validated = $this->validate(
            rules: [
                'key' => EnvVariableRules::keyRules(),
                'rule' => 'required',
                'description' => 'nullable|string'
            ],
            attributes: [
                'key' => $this->key, 
                'rule' => $this->rule,
                'description' => $this->description
            ]
        );
        
        //$this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);

        $rule = app(CreateRule::class)->handle(
            environment: $this->environment,
            key: $validated['key'],
            rule: $validated['rule'],
            description: $validated['description']
        );
        
        $this->reset('key', 'rule', 'description');
        Flux::toast("New rule for key '{$rule->key}' added.");
    }
    
    public function render()
    {
        return view('environment.environment-variable-rule-creator');
    }
}
