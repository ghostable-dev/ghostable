<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\SuggestEnvKeys;
use App\Environment\Actions\Validation\CreateRule;
use App\Environment\Enums\EnvironmentVariableRuleType;
use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Models\EnvironmentVariableRule;
use App\Environment\Rules\EnvVariableRules;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;

class EnvironmentValidationEditor extends EnvironmentComponent
{
    public string $key = '';
    
    public bool $is_required = false;
    
    public EnvironmentVariableRuleType $type = EnvironmentVariableRuleType::STRING;
    
    public ?string $description = null;
    
    public array $allowed_values = [];
    
    /**
     * Default sort key
     */
    public string $sortBy = 'key';

    /**
     * Table sort direction
     */
    public string $sortDirection = 'asc';
    
    /**
     * The ID of the rule currently selected for removal.
     *
     * Used to resolve the variable rule
     * instance prior to deletion.
     */
    public ?string $ruleToRemoveId = null;
    
    #[Computed]
    public function rules(): Collection
    {
        return $this->environment->rules;
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
    
    public function addRule(): void
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
    
    
    
    
    public function confirmRuleRemoval(EnvironmentVariableRule $rule): void
    {
        $this->ruleToRemoveId = $rule->id;

        $this->authorize('perform', [$rule->environment, TeamPermission::EditVariables]);

        Flux::modal('confirm-rule-removal')->show();
    }

    #[Computed]
    public function ruleToRemove(): ?EnvironmentVariableRule
    {
        return $this->environment->rules()
            ->firstWhere('id', $this->ruleToRemoveId);
    }
    
    #[Computed]
    public function ruleTypeOptions(): array
    {
        return EnvironmentVariableRuleType::cases();
    }

    public function removeRule(): void
    {
        $rule = $this->ruleToRemove;

        $this->authorize('perform', [$rule->environment, TeamPermission::EditVariables]);

        $rule->delete();
        // app(DeleteEnvVariable::class)->handle(
        //     var: $this->variableToRemove,
        //     deletedBy: Auth::user()
        // );

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        //$this->refreshVars();

        Flux::modal('confirm-rule-removal')->close();
        Flux::toast("Rule '{$rule->key}' removed.");

        $this->reset('ruleToRemoveId');
    }
    
    public function render()
    {
        return view('environment.environment-validation-editor');
    }
}
