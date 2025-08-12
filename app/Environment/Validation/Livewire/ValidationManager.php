<?php

namespace App\Environment\Validation\Livewire;

use App\Environment\Livewire\EnvironmentComponent;
use App\Environment\Validation\Actions\ResolveEnvironmentVariableRules;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use App\Team\Enums\TeamPermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class ValidationManager extends EnvironmentComponent
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
        $rules = resolve(ResolveEnvironmentVariableRules::class)
            ->handle($this->environment);

        if ($this->sortBy === 'key') {
            return $this->sortDirection === 'desc'
                ? $rules->sortByDesc('key')
                : $rules->sortBy('key');
        } elseif ($this->sortBy === 'updated_at') {
            return $this->sortDirection === 'desc'
                ? $rules->sortByDesc('updated_at')
                : $rules->sortBy('updated_at');
        }

        return $rules;
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
        $this->dispatch(
            VariableRuleEditor::LAUNCH,
            rule: $rule->id,
            targetEnvironment: $this->environment->id
        );
    }

    /**
     * Dispatch an event to open the rule deleter for the given rule.
     */
    public function removeRule(EnvironmentVariableRule $rule): void
    {
        $this->dispatch(
            VariableRuleDeleter::LAUNCH,
            rule: $rule->id,
            targetEnvironment: $this->environment->id
        );
    }

    /**
     * Dispatch an event to open the rule reinstater for the given rule.
     */
    public function reinstateRule(EnvironmentVariableRule $rule): void
    {
        $this->dispatch(
            VariableRuleReinstater::LAUNCH,
            rule: $rule->id,
            targetEnvironment: $this->environment->id
        );
    }

    /**
     * Refresh the list of environment variable rules after a new rule is added.
     */
    #[On([
        VariableRuleCreator::ADDED,
        VariableRuleEditor::UPDATED,
        VariableRuleDeleter::DELETED,
        VariableRuleReinstater::REINSTATED,
    ])]
    public function refreshRules(): void
    {
        $this->rules();
    }

    public function render()
    {
        return view('environment.validation.validation-manager');
    }
}
