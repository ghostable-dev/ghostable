<?php

namespace App\Environment\Validation\Actions;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Validation\Entities\CreateVariableRuleData;

class SuppressInheritedVariableRule extends VariableRuleAction
{
    /**
     * Suppress an inherited validation rule by creating a tombstone in the target environment.
     */
    public function handle(string $key, Environment $environment, ?User $suppressedBy = null): void
    {
        $data = new CreateVariableRuleData(
            environment: $environment,
            key: $key,
            isRequired: false,
            type: 'string',
            min: null,
            max: null,
            allowedValues: [],
            description: null,
            isOverride: false,
            isDeleted: true,
            createdBy: $suppressedBy,
        );

        $rule = app(CreateVariableRule::class)->handle($data);

        $this->logger->handle(rule: $rule, event: 'suppress-inherited', user: $suppressedBy);
    }
}
