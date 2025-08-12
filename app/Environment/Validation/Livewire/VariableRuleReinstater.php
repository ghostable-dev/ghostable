<?php

namespace App\Environment\Validation\Livewire;

use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\ReinstateInheritedVariableRule;
use App\Environment\Validation\Actions\ReinstateOverrideVariableRule;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use App\Team\Enums\TeamPermission;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class VariableRuleReinstater extends Component
{
    public const LAUNCH = 'variable-rule-reinstater:launch';

    public const REINSTATED = 'variable-rule-reinstater:reinstated';

    public bool $showing = false;

    public ?string $ruleId = null;

    public ?string $targetEnvironmentId = null;

    #[On(self::LAUNCH)]
    public function launchModal(EnvironmentVariableRule $rule, ?Environment $targetEnvironment = null): void
    {
        $environment = $targetEnvironment ?? $rule->environment;

        $this->authorize('perform', [$environment, TeamPermission::ManageValidationRules]);

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
    public function isOverride(): bool
    {
        return $this->rule?->is_override ?? false;
    }

    public function reinstateRule(): void
    {
        $this->authorize('perform', [$this->targetEnvironment, TeamPermission::ManageValidationRules]);

        if ($this->isOverride) {
            resolve(ReinstateOverrideVariableRule::class)->handle(
                rule: $this->rule,
                reinstatedBy: Auth::user()
            );
        } else {
            resolve(ReinstateInheritedVariableRule::class)->handle(
                rule: $this->rule,
                reinstatedBy: Auth::user()
            );
        }

        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);
        $this->dispatch(self::REINSTATED, $this->ruleId);

        $this->closeAndReset();
    }

    protected function closeAndReset(): void
    {
        $this->showing = false;
        $this->resetValues();
    }

    protected function resetValues(): void
    {
        $this->reset('ruleId', 'targetEnvironmentId');
    }

    public function render()
    {
        return view('environment.validation.variable-rule-reinstater');
    }
}
