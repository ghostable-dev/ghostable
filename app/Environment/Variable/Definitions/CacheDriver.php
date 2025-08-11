<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class CacheDriver extends VariableDefinition
{
    public function key(): string
    {
        return 'CACHE_DRIVER';
    }

    public function description(): ?string
    {
        return 'The default cache driver your application should use.';
    }

    public function suggestedValues(): array
    {
        return ['file', 'redis', 'array', 'memcached', 'database', 'dynamodb', 'null'];
    }

    public function group(): VariableGroup
    {
        return VariableGroup::Cache;
    }

    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues())),
        ];
    }
}
