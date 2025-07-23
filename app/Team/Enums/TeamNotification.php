<?php

namespace App\Team\Enums;

use App\Project\Notifications\ProjectActivityNotification;
use App\Team\Models\Team;
use App\Team\Notifications\AccessChangeNotification;
use App\Team\Notifications\MembershipActivityNotification;
use App\Team\Notifications\TeamSettingsChangedNotification;

enum TeamNotification: string
{
    case MEMBERSHIP_ACTIVITY = 'membership_activity';
    case ACCESS_CHANGE = 'access_change';
    case TEAM_SETTINGS_CHANGED = 'team_settings_changed';
    case PROJECT_ACTIVITY = 'project_activity';

    public function label(): string
    {
        return match ($this) {
            self::MEMBERSHIP_ACTIVITY => 'Membership Activity',
            self::ACCESS_CHANGE => 'Role & Permission Changes',
            self::TEAM_SETTINGS_CHANGED => 'Team Settings Changes',
            self::PROJECT_ACTIVITY => 'Project Activity',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MEMBERSHIP_ACTIVITY => 'Notifies when users are invited, join, or are removed from the team.',
            self::ACCESS_CHANGE => 'Alerts when a team member’s role or access permissions are updated.',
            self::TEAM_SETTINGS_CHANGED => 'Fires when core team-level settings are modified.',
            self::PROJECT_ACTIVITY => 'Notifies when projects are created or deleted from the team.',
        };
    }

    public function notification(): string
    {
        return match ($this) {
            self::MEMBERSHIP_ACTIVITY => MembershipActivityNotification::class,
            self::ACCESS_CHANGE => AccessChangeNotification::class,
            self::TEAM_SETTINGS_CHANGED => TeamSettingsChangedNotification::class,
            self::PROJECT_ACTIVITY => ProjectActivityNotification::class
        };
    }

    public function isAvailableForTeam(Team $team): bool
    {
        return match ($this) {
            self::MEMBERSHIP_ACTIVITY,
            self::ACCESS_CHANGE,
            self::PROJECT_ACTIVITY => ! $team->isPersonal(),
            self::TEAM_SETTINGS_CHANGED => true,
        };
    }

    public function requiredPermission(): string
    {
        return match ($this) {
            self::MEMBERSHIP_ACTIVITY => 'manageMembers',
            self::ACCESS_CHANGE => 'manageAccessControls',
            self::TEAM_SETTINGS_CHANGED,
            self::PROJECT_ACTIVITY => 'manageSettings',
        };
    }
}
