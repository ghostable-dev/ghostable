<?php

namespace App\Environment\Notifications;

enum EnvironmentNotification: string
{
    case VARIABLE_UPDATED = 'variable_updated';

    public function label(): string
    {
        return match ($this) {
            self::VARIABLE_UPDATED => 'Variable Updated',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::VARIABLE_UPDATED => 'An environment variable was updated.',
        };
    }

    public function notification(): string
    {
        return match ($this) {
            self::VARIABLE_UPDATED => VariableUpdatedNotification::class,
        };
    }
}
