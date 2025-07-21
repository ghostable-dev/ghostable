<?php

namespace App\Team\Notifications;

enum TeamNotification: string
{
    case PROJECT_CREATED = 'project_created';
    case PROJECT_DELETED = 'project_deleted';

    public function label(): string
    {
        return match ($this) {
            self::PROJECT_CREATED => 'Project Created',
            self::PROJECT_DELETED => 'Project Deleted',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PROJECT_CREATED => 'A new project was created in this team.',
            self::PROJECT_DELETED => 'A project was deleted from this team.',
        };
    }

    public function notification(): string
    {
        return match ($this) {
            self::PROJECT_CREATED => ProjectCreatedNotification::class,
            self::PROJECT_DELETED => ProjectDeletedNotification::class,
        };
    }
}
