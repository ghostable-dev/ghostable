<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;

class CacheDriver extends EnvironmentVariableDefinition
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

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Cache;
    }
    
    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues()))
        ];
    }
}