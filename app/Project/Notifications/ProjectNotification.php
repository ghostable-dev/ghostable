<?php

namespace App\Project\Notifications;

enum ProjectNotification: string
{
    case ENVIRONMENT_CREATED = 'environment_created';
    case ENVIRONMENT_DELETED = 'environment_deleted';

    public function label(): string
    {
        return match ($this) {
            self::ENVIRONMENT_CREATED => 'Environment Created',
            self::ENVIRONMENT_DELETED => 'Environment Deleted',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ENVIRONMENT_CREATED => 'A new environment was created in this project.',
            self::ENVIRONMENT_DELETED => 'An environment was deleted from this project.',
        };
    }

    public function notification(): string
    {
        return match ($this) {
            self::ENVIRONMENT_CREATED => EnvironmentCreatedNotification::class,
            self::ENVIRONMENT_DELETED => EnvironmentDeletedNotification::class,
        };
    }
}
