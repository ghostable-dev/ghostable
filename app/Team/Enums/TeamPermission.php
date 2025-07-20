<?php

namespace App\Team\Enums;

enum TeamPermission: string
{
    // Teams
    case ManageTeamMembers = 'team:manage-members';
    case ManageBilling = 'team:manage-billing';
    case ManageTeamSettings = 'team:manage-settings';
    case ManageAccessControls = 'team:manage-access-controls';
    case ViewAuditLogs = 'team:view-audit-logs';

    // Projects
    case CreateProjects = 'project:create';
    case ManageProjectSettings = 'project:manage-settings';
    case DeleteProjects = 'project:delete';

    // Environments
    case CreateEnvironments = 'env:create';
    case DeleteEnvironments = 'env:delete';
    case ManageEnvironmentSettings = 'env:manage-settings';

    // Variables
    case ViewVariables = 'var:view';
    case EditVariables = 'var:edit';
    case PushFile = 'var:push';
    case ManageValidationRules = 'var:manage-rules';

    // Secrets
    case ViewSecrets = 'secret:view';
    case EditSecrets = 'secret:edit';

    /**
     * Get the display group for organizing permissions in the UI.
     */
    public function group(): string
    {
        return match ($this) {
            self::ManageTeamMembers,
            self::ManageBilling,
            self::ManageTeamSettings,
            self::ManageAccessControls,
            self::ViewAuditLogs => 'Team',

            self::CreateProjects,
            self::DeleteProjects,
            self::ManageProjectSettings => 'Projects',

            self::CreateEnvironments,
            self::DeleteEnvironments,
            self::ManageEnvironmentSettings => 'Environments',

            self::ViewVariables,
            self::EditVariables,
            self::PushFile,
            self::ManageValidationRules => 'Variables',

            self::ViewSecrets,
            self::EditSecrets => 'Secrets',
        };
    }

    /**
     * Get the human-readable label for this permission.
     */
    public function label(): string
    {
        return match ($this) {
            // Teams
            self::ManageTeamMembers => 'Manage team members',
            self::ManageBilling => 'Manage billing and subscriptions',
            self::ManageTeamSettings => 'Manage team settings',
            self::ManageAccessControls => 'Manage access controls',
            self::ViewAuditLogs => 'View team audit logs',

            // Projects
            self::CreateProjects => 'Create new projects',
            self::ManageProjectSettings => 'Manage project settings',
            self::DeleteProjects => 'Delete projects',

            // Environments
            self::CreateEnvironments => 'Create environments',
            self::DeleteEnvironments => 'Delete environments',
            self::ManageEnvironmentSettings => 'Manage environment settings',

            // Variables
            self::ViewVariables => 'View environment variables',
            self::EditVariables => 'Edit environment variables',
            self::PushFile => 'Push full environment file',
            self::ManageValidationRules => 'Manage validation rules',

            // Secrets
            self::ViewSecrets => 'View secrets',
            self::EditSecrets => 'Manage secrets',
        };
    }

    /**
     * Permissions that can be overridden at the project level.
     *
     * @return TeamPermission[]
     */
    public static function projectOverrides(): array
    {
        return [
            self::ManageProjectSettings,
            self::CreateEnvironments,
            self::DeleteEnvironments,
            ...self::environmentOverrides(),
        ];
    }

    /**
     * Permissions that can be overridden at the environment level.
     *
     * @return TeamPermission[]
     */
    public static function environmentOverrides(): array
    {
        return [
            self::ViewVariables,
            self::EditVariables,
            self::PushFile,
            self::ManageValidationRules,
            self::ViewSecrets,
            self::EditSecrets,
        ];
    }
}
