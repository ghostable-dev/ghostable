<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class CacheDriver extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'CACHE_DRIVER';
    }

    public function rule(): string
    {
        return 'in:file,redis,array,memcached,database,dynamodb,null';
    }

    public function description(): ?string
    {
        return 'The default cache driver your application should use.';
    }

    public function suggestedValues(): array
    {
        return ['file', 'redis', 'array', 'memcached', 'database', 'dynamodb', 'null'];
    }

    public function inputType(): ?string
    {
        return 'select';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Cache;
    }
}