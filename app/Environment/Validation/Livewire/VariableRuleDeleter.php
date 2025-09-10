<?php

namespace App\Environment\Validation\Livewire;

use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\DeleteVariableRule;
use App\Environment\Validation\Actions\SuppressInheritedVariableRule;
use App\Environment\Validation\Actions\SuppressOverrideVariableRule;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use App\Organization\Enums\OrganizationPermission;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class VariableRuleDeleter extends Component
{
    public const LAUNCH = 'variable-rule-deleter:launch';

    public const DELETED = 'variable-rule-deleter:deleted';

    public bool $showing = false;

    public ?string $ruleId = null;

    public ?string $targetEnvironmentId = null;

    public string $deleteMode = 'delete';

    #[On(self::LAUNCH)]
    public function launchModal(EnvironmentVariableRule $rule, ?Environment $targetEnvironment = null): void
    {
        $environment = $targetEnvironment ?? $rule->environment;

        $this->authorize('perform', [$environment, OrganizationPermission::ManageValidationRules]);

        if (! $rule->belongsToEnvironment($environment) && ! $environment->isDescendantOf($rule->environment)) {
            abort(403, 'Invalid inheritance.');
        }

        $this->ruleId = $rule->id;
        $this->targetEnvironmentId = $environment->id;
        $this->showing = true;
    }

    #[Computed]
    public function rule(): ?EnvironmentVariableRule
    {
        return $this->ruleId ? EnvironmentVariableRule::find($this->ruleId) : null;
    }

    #[Computed]
    public function targetEnvironment(): ?Environment
    {
        if ($this->targetEnvironmentId) {
            return Environment::find($this->targetEnvironmentId);
        }

        return $this->rule?->environment;
    }

    #[Computed]
    public function isLocalToTarget(): bool
    {
        return $this->rule?->belongsToEnvironment($this->targetEnvironment) ?? true;
    }

    #[Computed]
    public function isOverride(): bool
    {
        return $this->rule?->is_override ?? false;
    }

    public function removeRule(): void
    {
        $this->authorize('perform', [$this->targetEnvironment, OrganizationPermission::ManageValidationRules]);

        if (! $this->isLocalToTarget) {
            $this->suppressInherited();
        } elseif ($this->isOverride && $this->deleteMode === 'suppress') {
            $this->suppressOverride();
        } else {
            $this->deleteRule();
        }

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        $this->dispatch(self::DELETED, $this->ruleId);

        $this->closeAndReset();
    }

    protected function suppressInherited(): void
    {
        resolve(SuppressInheritedVariableRule::class)->handle(
            key: $this->rule->key,
            environment: $this->targetEnvironment,
            suppressedBy: Auth::user()
        );

        $this->successToast(
            heading: 'Inherited Rule Suppressed',
            text: sprintf('The rule "%s" will no longer be inherited in this environment.', $this->rule->key)
        );
    }

    protected function suppressOverride(): void
    {
        resolve(SuppressOverrideVariableRule::class)->handle(
            rule: $this->rule,
            suppressedBy: Auth::user()
        );

        $this->successToast(
            heading: 'Override Suppressed',
            text: sprintf('The rule "%s" has been blocked from inheriting a value.', $this->rule->key)
        );
    }

    protected function deleteRule(): void
    {
        resolve(DeleteVariableRule::class)->handle(
            rule: $this->rule,
            user: Auth::user()
        );

        $this->successToast(
            heading: 'Rule Deleted',
            text: sprintf('The rule "%s" was deleted from this environment.', $this->rule->key)
        );
    }

    protected function successToast(string $heading, string $text): void
    {
        Flux::toast(variant: 'success', heading: $heading, text: $text);
    }

    protected function closeAndReset(): void
    {
        $this->showing = false;
        $this->resetValues();
    }

    protected function resetValues(): void
    {
        $this->reset('deleteMode', 'ruleId', 'targetEnvironmentId');
    }

    public function render()
    {
        return view('environment.validation.variable-rule-deleter');
    }
}
