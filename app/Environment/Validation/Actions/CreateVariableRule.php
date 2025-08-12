<?php

namespace App\Environment\Validation\Actions;

use App\Environment\Validation\Entities\CreateVariableRuleData;
use App\Environment\Validation\Models\EnvironmentVariableRule;

class CreateVariableRule extends VariableRuleAction
{
    public function handle(CreateVariableRuleData $data): EnvironmentVariableRule
    {
        $rule = new EnvironmentVariableRule([
            'key' => $data->key,
            'is_required' => $data->isRequired,
            'type' => $data->type->value,
            'min' => $data->min,
            'max' => $data->max,
            'allowed_values' => $data->allowedValues,
            'description' => $data->description,
            'is_override' => $data->isOverride,
            'is_deleted' => $data->isDeleted,
        ]);

        $rule->environment()->associate($data->environment);

        $rule->save();

        $this->logger->handle(rule: $rule, event: 'created', user: $data->createdBy);

        return $rule;
    }
}
