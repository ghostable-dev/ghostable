<?php

namespace App\Project\Enums;

enum ProjectNotification: string
{
    case PROJECT_SETTINGS_CHANGED = 'project_settings_changed';
    case ENVIRONMENT_ACTIVITY = 'environment_activity';

    public function label(): string
    {
        return match ($this) {
            self::PROJECT_SETTINGS_CHANGED => 'Project Settings Changed',
            self::ENVIRONMENT_ACTIVITY => 'Environment Activity',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PROJECT_SETTINGS_CHANGED => 'Fires when project settings are modified (e.g. name, notifications, etc).',
            self::ENVIRONMENT_ACTIVITY => 'Notifies when environments are created or deleted.',
        };
    }
}
