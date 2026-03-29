<?php

namespace App\Organization\Enums;

enum OrganizationRole: string
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
            self::ADMIN => 'Full access to organization, billing, projects, and environments.',
            self::BILLING_ONLY => 'Access to billing settings only. No access to projects or environments.',
            self::DEVELOPER => 'Create and manage projects and environments. No access to billing or organization settings.',
            self::DEVELOPER_READ_ONLY => 'Read-only access to projects and environments.',
            self::AUDITOR => 'View audit logs for security monitoring. No edit permissions.',
        };
    }

    /**
     * Get the default permissions for the given role.
     *
     * @return OrganizationPermission[]
     */
    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN => OrganizationPermission::cases(),

            self::BILLING_ONLY => [
                OrganizationPermission::ManageBilling,
            ],

            self::DEVELOPER => [
                // Project permissions
                OrganizationPermission::CreateProjects,
                OrganizationPermission::ManageProjectSettings,

                // Environment permissions
                OrganizationPermission::CreateEnvironments,
                OrganizationPermission::DeleteEnvironments,
                OrganizationPermission::ManageEnvironmentSettings,

                // Variable permissions
                OrganizationPermission::ViewVariables,
                OrganizationPermission::EditVariables,
                OrganizationPermission::ViewContext,
                OrganizationPermission::EditNote,
                OrganizationPermission::Comment,
                OrganizationPermission::ViewVersionChangeNotes,
                OrganizationPermission::PushFile,

                // Secret permissions
                OrganizationPermission::ViewSecrets,
                OrganizationPermission::EditSecrets,
            ],

            self::DEVELOPER_READ_ONLY => [
                OrganizationPermission::ViewVariables,
                OrganizationPermission::ViewContext,
                OrganizationPermission::ViewVersionChangeNotes,
                OrganizationPermission::ViewSecrets,
            ],

            self::AUDITOR => [
                OrganizationPermission::ViewAuditLogs,
            ],
        };
    }

    /**
     * Determine if this role includes the given permission.
     */
    public function hasPermission(OrganizationPermission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
