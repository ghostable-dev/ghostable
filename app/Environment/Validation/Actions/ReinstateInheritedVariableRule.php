<?php

namespace App\Environment\Validation\Actions;

use App\Account\Models\User;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use LogicException;

class ReinstateInheritedVariableRule extends VariableRuleAction
{
    /**
     * Reinstate an inherited validation rule by removing its tombstone record.
     */
    public function handle(EnvironmentVariableRule $rule, ?User $reinstatedBy = null): void
    {
        if (! $rule->is_deleted) {
            throw new LogicException('Cannot reinstate an active rule.');
        }

        $rule->delete();

        $this->logger->handle(rule: $rule, event: 'reinstate-inherited', user: $reinstatedBy);
    }
}
