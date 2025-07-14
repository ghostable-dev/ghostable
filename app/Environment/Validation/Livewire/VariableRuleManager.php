<?php

namespace App\Environment\Validation\Livewire;

use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Livewire\EnvironmentComponent;
use App\Environment\Validation\Actions\DeleteVariableRule;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use App\Team\Enums\TeamPermission;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class VariableRuleManager extends EnvironmentComponent
{
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
    
    /**
     * Update the sorting configuration for validation rules.
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
     * Get the list of environment variable rules for the current environment,
     * sorted by the given column and direction.
     */
    #[Computed]
    public function rules(): Collection
    {
        return $this->environment->rules()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->get();
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
     * Dispatch an event to launch the Create Rule modal component.
     */
    public function launchCreateRuleModal(): void
    {
        $this->dispatch(VariableRuleCreator::LAUNCH);
    }
    
    /**
     * Dispatch an event to open the editor for the given rule.
     */
    public function editRule(EnvironmentVariableRule $rule): void
    {
        $this->dispatch(VariableRuleEditor::LAUNCH, $rule->id);
    }
    
    /**
     * Prepare to remove an environment variable rule by setting the rule ID,
     * performing an authorization check, and showing the confirmation modal.
     */
    public function confirmRuleRemoval(EnvironmentVariableRule $rule): void
    {
        $this->ruleToRemoveId = $rule->id;

        $this->authorize('perform', [$rule->environment, TeamPermission::ManageValidationRules]);

        Flux::modal('confirm-rule-removal')->show();
    }

    /**
     * Get the environment variable rule marked for removal, if any.
     *
     * This looks up the rule by its ID within the current environment's rules.
     */
    #[Computed]
    public function ruleToRemove(): ?EnvironmentVariableRule
    {
        return $this->environment->rules()->firstWhere('id', $this->ruleToRemoveId);
    }
    
    /**
     * Permanently delete the selected environment variable rule.
     */
    public function removeRule(): void
    {
        $rule = $this->ruleToRemove;

        $this->authorize('perform', [$rule->environment, TeamPermission::ManageValidationRules]);

        app(DeleteVariableRule::class)->handle(rule: $rule, user: Auth::user());

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        $this->refreshRules();

        Flux::modal('confirm-rule-removal')->close();
        Flux::toast("Rule '{$rule->key}' removed.");

        $this->reset('ruleToRemoveId');
    }
    
    /**
     * Refresh the list of environment variable rules after a new rule is added.
     */
    #[On([
        VariableRuleCreator::ADDED, 
        VariableRuleEditor::UPDATED
    ])]
    public function refreshRules(): void
    {
        $this->rules();
    }
    
    public function render()
    {
        return view('environment.validation.variable-rule-manager');
    }
}
