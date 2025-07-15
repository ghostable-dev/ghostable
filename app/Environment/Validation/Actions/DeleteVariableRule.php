<?php

namespace App\Environment\Validation\Actions;

use App\Account\Models\User;
use App\Environment\Validation\Models\EnvironmentVariableRule;

class DeleteVariableRule extends VariableRuleAction
{
    public function handle(EnvironmentVariableRule $rule, ?User $user = null): void
    {
        $rule->delete();

        $this->logger->handle(rule: $rule, event: 'deleted', user: $user);
    }
}
