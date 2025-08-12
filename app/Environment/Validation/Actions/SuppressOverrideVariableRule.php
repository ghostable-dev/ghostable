<?php

namespace App\Environment\Validation\Actions;

use App\Account\Models\User;
use App\Environment\Validation\Models\EnvironmentVariableRule;

class SuppressOverrideVariableRule extends VariableRuleAction
{
    /**
     * Suppress a locally overridden validation rule by marking it as deleted.
     */
    public function handle(EnvironmentVariableRule $rule, ?User $suppressedBy = null): void
    {
        $rule->update([
            'is_deleted' => true,
        ]);

        $this->logger->handle(rule: $rule, event: 'suppress-override', user: $suppressedBy);
    }
}
