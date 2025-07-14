<?php

namespace App\Environment\Validation\Livewire;

use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Validation\Actions\UpdateVariableRule;
use App\Environment\Validation\Entities\UpdateVariableRuleData;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use App\Environment\Validation\Rules\VariableRuleFormRules;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class VariableRuleEditor extends Component
{   
    /**
     * Event name used to trigger the variable rule editor modal.
     */
    public const LAUNCH = 'variable-rule-editor:launch';
    
    /**
     * Livewire event name dispatched when a rule has been successfully updated.
     */
    public const UPDATED = 'variable-rule-editor:updated';
    
    /**
     * Indicates whether the rule editor modal is currently visible.
     */
    public bool $showing = false;
    
    /**
     * The ID of the environment variable rule currently being edited.
     */
    public ?string $ruleId = null;
    
    /**
     * The key of the environment variable the rule applies to.
     */
    public string $key = '';
    
    /**
     * Whether this variable is required in the environment.
     */
    public bool $is_required = false;
    
    /**
     * The expected type of the variable (string, integer, enum, etc).
     */
    public ?EnvironmentVariableRuleType $type = null;
    
    /**
     * The minimum.
     */
    public ?int $min = null;
    
    /**
     * The maximum.
     */
    public ?int $max = null;
    
    /**
     * The list of allowed values (applicable when type is enum).
     */
    public array $allowed_values = [];
    
    /**
     * Optional description of the rule’s purpose or enforcement intent.
     */
    public ?string $description = null;
    
    /**
     * Launch the environment variable rule editor modal with the selected rule.
     *
     * This method:
     * - Authorizes the user using the ManageValidationRules permission
     * - Loads the rule's current data into the component state
     * - Opens the modal for editing
     */
    #[On(self::LAUNCH)]
    public function launchEditorModal(EnvironmentVariableRule $rule): void
    {
        $this->authorize('perform', [$rule->environment, TeamPermission::ManageValidationRules]);

        $this->ruleId = $rule->id;
        
        $this->key = $rule->key;
        $this->is_required = $rule->is_required;
        $this->type = $rule->type;
        $this->min = $rule->min;
        $this->max = $rule->max;
        $this->allowed_values = $rule->allowed_values;
        $this->description = $rule->description;

        $this->showing = true;
    }
    
    /**
     * Retrieve the environment variable rule currently being edited.
     *
     * Uses the stored rule ID to fetch the corresponding rule instance from the database.
     * Returns null if no rule is selected.
     */
    #[Computed]
    public function rule(): ?EnvironmentVariableRule
    {
        return EnvironmentVariableRule::find($this->ruleId);
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

    public function update(): void
    {
        $this->authorize('perform', [$this->rule->environment, TeamPermission::ManageValidationRules]);

        if ($this->noChangesWereMade()) {
            $this->showing = false;
            $this->resetAll();
            return;
        }
        
        $rules = VariableRuleFormRules::updateRules($this->rule);

        $validated = $this->validate($rules);

        app(UpdateVariableRule::class)->handle($this->toUpdateRuleData($validated));

        Flux::toast(
            variant: 'success',
            heading: 'Rule Updated',
            text: "Rule for \"{$this->key}\" was successfully updated."
        );
        
        $this->dispatch(self::UPDATED, $this->ruleId);
        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        
        $this->showing = false;
        $this->resetAll();
    }
    
    /**
     * Determine whether any changes have been made to the currently loaded rule.
     *
     * Compares all editable fields against the existing rule to detect whether
     * the form input has diverged from the persisted state. Used to avoid
     * unnecessary updates or to toggle password confirmation logic.
     */
    #[Computed]
    public function noChangesWereMade(): bool
    {
        $rule = $this->rule;

        return $rule
            && $this->key === $rule->key
            && $this->is_required == $rule->is_required
            && $this->type->value === $rule->type->value
            && $this->min === $rule->min
            && $this->max === $rule->max
            && $this->description === $rule->description
            && $this->allowed_values == $rule->allowed_values;
    }

    /**
     * Transform validated input into a structured UpdateVariableRuleData DTO.
     *
     * This method packages the input fields into a value object that represents
     * an update to an environment variable rule, including the authenticated user.
     */
    private function toUpdateRuleData(array $input): UpdateVariableRuleData
    {
        return new UpdateVariableRuleData(
            rule: $this->rule,
            key: $input['key'],
            isRequired: $input['is_required'],
            type: $input['type'],
            min: $input['min'] ?? null,
            max: $input['max'] ?? null,
            allowedValues: $input['allowed_values'] ?? [],
            description: $input['description'] ?? null,
            updatedBy: Auth::user(),
        );
    }
    
    /**
     * Reset all component state related to the rule editor form.
     *
     * This clears the currently loaded rule ID and all associated form fields,
     * preparing the component for a fresh state or modal closure.
     */
    private function resetAll(): void
    {
        $this->reset([
            'ruleId',
            'key',
            'is_required',
            'type',
            'min',
            'max',
            'allowed_values',
            'description'
        ]);
    }

    public function render()
    {
        return view('environment.validation.variable-rule-editor');
    }
}