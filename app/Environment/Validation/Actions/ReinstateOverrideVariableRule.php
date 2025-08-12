<?php

namespace App\Environment\Validation\Actions;

use App\Account\Models\User;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use LogicException;

class ReinstateOverrideVariableRule extends VariableRuleAction
{
    /**
     * Reinstate a locally overridden validation rule that was previously suppressed.
     */
    public function handle(EnvironmentVariableRule $rule, ?User $reinstatedBy = null): void
    {
        if (! $rule->is_deleted) {
            throw new LogicException('Cannot reinstate unsuppressed rule.');
        }

        $rule->update([
            'is_deleted' => false,
        ]);

        $this->logger->handle(rule: $rule, event: 'reinstate-override', user: $reinstatedBy);
    }
}
