<?php

namespace App\Environment\Validation\Actions;

use App\Environment\Validation\Entities\UpdateVariableRuleData;
use App\Environment\Validation\Models\EnvironmentVariableRule;

class UpdateVariableRule extends VariableRuleAction
{
    public function handle(UpdateVariableRuleData $data): EnvironmentVariableRule
    {
        $data->rule->update([
            'key' => $data->key,
            'is_required' => $data->isRequired,
            'type' => $data->type,
            'min' => $data->min,
            'max' => $data->max,
            'allowed_values' => $data->allowedValues,
            'description' => $data->description,
            'is_override' => $data->isOverride,
            'is_deleted' => $data->isDeleted,
        ]);

        $this->logger->handle(rule: $data->rule, event: 'updated', user: $data->updatedBy);

        return $data->rule;
    }
}
