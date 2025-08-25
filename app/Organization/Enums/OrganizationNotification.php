<?php

namespace App\Organization\Enums;

use App\Organization\Models\Organization;

enum OrganizationNotification: string
{
    case MEMBERSHIP_ACTIVITY = 'membership_activity';
    case ACCESS_CHANGE = 'access_change';
    case ORGANIZATION_SETTINGS_CHANGED = 'organization_settings_changed';
    case PROJECT_ACTIVITY = 'project_activity';

    public function label(): string
    {
        return match ($this) {
            self::MEMBERSHIP_ACTIVITY => 'Membership Activity',
            self::ACCESS_CHANGE => 'Role & Permission Changes',
            self::ORGANIZATION_SETTINGS_CHANGED => 'Organization Settings Changes',
            self::PROJECT_ACTIVITY => 'Project Activity',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MEMBERSHIP_ACTIVITY => 'Notifies when users are invited, join, or are removed from the organization.',
            self::ACCESS_CHANGE => 'Alerts when a organization member’s role or access permissions are updated.',
            self::ORGANIZATION_SETTINGS_CHANGED => 'Fires when core organization-level settings are modified.',
            self::PROJECT_ACTIVITY => 'Notifies when projects are created or deleted from the organization.',
        };
    }

    public function isAvailableForOrganization(Organization $organization): bool
    {
        return match ($this) {
            self::MEMBERSHIP_ACTIVITY,
            self::ACCESS_CHANGE,
            self::PROJECT_ACTIVITY => ! $organization->isPersonal(),
            self::ORGANIZATION_SETTINGS_CHANGED => true,
        };
    }

    public function requiredPermission(): string
    {
        return match ($this) {
            self::MEMBERSHIP_ACTIVITY => 'manageMembers',
            self::ACCESS_CHANGE => 'manageAccessControls',
            self::ORGANIZATION_SETTINGS_CHANGED,
            self::PROJECT_ACTIVITY => 'manageSettings',
        };
    }
}
