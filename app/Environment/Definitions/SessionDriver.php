<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class SessionDriver extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'SESSION_DRIVER';
    }

    public function rule(): string
    {
        return 'required|in:file,cookie,database,redis,memcached,dynamodb,array';
    }

    public function description(): ?string
    {
        return 'The session driver used to handle user sessions.';
    }

    public function suggestedValues(): array
    {
        return ['file', 'cookie', 'database', 'redis', 'memcached', 'dynamodb', 'array'];
    }

    public function inputType(): ?string
    {
        return 'select';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }
}