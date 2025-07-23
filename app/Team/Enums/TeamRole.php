<?php

namespace App\Team\Enums;

enum TeamRole: string
{
    case ADMIN = 'admin';
    case BILLING_ONLY = 'billing_only';
    case DEVELOPER = 'developer';
    case DEVELOPER_READ_ONLY = 'developer_read_only';
    case AUDITOR = 'auditor';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::BILLING_ONLY => 'Billing Only',
            self::DEVELOPER => 'Developer',
            self::DEVELOPER_READ_ONLY => 'Developer (Read Only)',
            self::AUDITOR => 'Auditor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ADMIN => 'Full access to team, billing, projects, and environments.',
            self::BILLING_ONLY => 'Access to billing settings only. No access to projects or environments.',
            self::DEVELOPER => 'Create and manage projects and environments. No access to billing or team settings.',
            self::DEVELOPER_READ_ONLY => 'Read-only access to projects and environments.',
            self::AUDITOR => 'View audit logs for security monitoring. No edit permissions.',
        };
    }

    /**
     * Get the default permissions for the given role.
     *
     * @return TeamPermission[]
     */
    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN => TeamPermission::cases(),

            self::BILLING_ONLY => [
                TeamPermission::ManageBilling,
            ],

            self::DEVELOPER => [
                // Project permissions
                TeamPermission::CreateProjects,
                TeamPermission::ManageProjectSettings,

                // Environment permissions
                TeamPermission::CreateEnvironments,
                TeamPermission::DeleteEnvironments,
                TeamPermission::ManageEnvironmentSettings,

                // Variable permissions
                TeamPermission::ViewVariables,
                TeamPermission::EditVariables,
                TeamPermission::PushFile,

                // Secret permissions
                TeamPermission::ViewSecrets,
                TeamPermission::EditSecrets,
            ],

            self::DEVELOPER_READ_ONLY => [
                TeamPermission::ViewVariables,
                TeamPermission::ViewSecrets,
            ],

            self::AUDITOR => [
                TeamPermission::ViewAuditLogs,
            ],
        };
    }

    /**
     * Determine if this role includes the given permission.
     */
    public function hasPermission(TeamPermission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
