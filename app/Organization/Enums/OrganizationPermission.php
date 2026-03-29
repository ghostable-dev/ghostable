<?php

namespace App\Organization\Enums;

enum OrganizationPermission: string
{
    // Organizations
    case ManageOrganizationMembers = 'organization:manage-members';
    case ManageBilling = 'organization:manage-billing';
    case ManageOrganizationSettings = 'organization:manage-settings';
    case ManageAccessControls = 'organization:manage-access-controls';
    case ViewAuditLogs = 'organization:view-audit-logs';

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
    case ViewContext = 'var:view-context';
    case EditNote = 'var:edit-note';
    case Comment = 'var:comment';
    case ViewVersionChangeNotes = 'var:view-version-change-notes';
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
            self::ManageOrganizationMembers,
            self::ManageBilling,
            self::ManageOrganizationSettings,
            self::ManageAccessControls,
            self::ViewAuditLogs => 'Organization',

            self::CreateProjects,
            self::DeleteProjects,
            self::ManageProjectSettings => 'Projects',

            self::CreateEnvironments,
            self::DeleteEnvironments,
            self::ManageEnvironmentSettings => 'Environments',

            self::ViewVariables,
            self::EditVariables,
            self::ViewContext,
            self::EditNote,
            self::Comment,
            self::ViewVersionChangeNotes,
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
            // Organizations
            self::ManageOrganizationMembers => 'Manage organization members',
            self::ManageBilling => 'Manage billing and subscriptions',
            self::ManageOrganizationSettings => 'Manage organization settings',
            self::ManageAccessControls => 'Manage access controls',
            self::ViewAuditLogs => 'View organization audit logs',

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
            self::ViewContext => 'View variable context',
            self::EditNote => 'Edit variable notes',
            self::Comment => 'Comment on variables',
            self::ViewVersionChangeNotes => 'View variable change reasons',
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
     * @return OrganizationPermission[]
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
     * @return OrganizationPermission[]
     */
    public static function environmentOverrides(): array
    {
        return [
            self::ViewVariables,
            self::EditVariables,
            self::ViewContext,
            self::EditNote,
            self::Comment,
            self::ViewVersionChangeNotes,
            self::PushFile,
            self::ManageValidationRules,
            self::ViewSecrets,
            self::EditSecrets,
        ];
    }
}
