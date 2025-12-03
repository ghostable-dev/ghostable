<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class CacheDriver extends VariableDefinition
{
    public function key(): string
    {
        return 'CACHE_DRIVER';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The default cache driver your application should use.';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['file', 'redis', 'array', 'memcached', 'database', 'dynamodb', 'null'];
    }

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Cache;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [];
    }
}
