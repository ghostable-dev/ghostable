<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class PusherAppKey extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'PUSHER_APP_KEY';
    }

    public function rule(): string
    {
        return 'required|string|max:128';
    }

    public function description(): ?string
    {
        return 'Your Pusher application key.';
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