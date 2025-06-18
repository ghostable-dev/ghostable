<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class PusherAppId extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'PUSHER_APP_ID';
    }

    public function rule(): string
    {
        return 'required|string|max:64';
    }

    public function description(): ?string
    {
        return 'Your Pusher application ID.';
    }

    public function inputType(): ?string
    {
        return 'text';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Pusher;
    }
}