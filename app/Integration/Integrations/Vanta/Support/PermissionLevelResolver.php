<?php

declare(strict_types=1);

namespace App\Integration\Integrations\Vanta\Support;

use App\Account\Models\User;
use App\Integration\Integrations\Vanta\Enums\PermissionLevel;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Illuminate\Support\Str;

class PermissionLevelResolver
{
    private const WRITE_PERMISSIONS = [
        OrganizationPermission::ManageOrganizationMembers,
        OrganizationPermission::ManageBilling,
        OrganizationPermission::ManageOrganizationSettings,
        OrganizationPermission::ManageAccessControls,
        OrganizationPermission::CreateProjects,
        OrganizationPermission::ManageProjectSettings,
        OrganizationPermission::DeleteProjects,
        OrganizationPermission::CreateEnvironments,
        OrganizationPermission::DeleteEnvironments,
        OrganizationPermission::ManageEnvironmentSettings,
        OrganizationPermission::EditVariables,
        OrganizationPermission::PushFile,
        OrganizationPermission::ManageValidationRules,
        OrganizationPermission::EditSecrets,
    ];

    private const READ_PERMISSIONS = [
        OrganizationPermission::ViewAuditLogs,
        OrganizationPermission::ViewVariables,
        OrganizationPermission::ViewSecrets,
    ];

    public function resolve(User $user, Organization $organization, ?string $role): PermissionLevel
    {
        if ($user->isOrganizationAdmin($organization)) {
            return PermissionLevel::ADMIN;
        }

        $role = $role ? Str::lower($role) : null;
        $roleEnum = $role ? OrganizationRole::tryFrom($role) : null;

        $hasWrite = $this->hasWritePermissions($roleEnum?->permissions() ?? []);
        $hasRead = $hasWrite || $this->hasReadPermissions($roleEnum?->permissions() ?? []);

        foreach ($organization->projects as $project) {
            $projectOverrides = $project->permissionOverrides->where('user_id', $user->id);
            $hasWrite = $hasWrite || $this->hasWritePermissions($projectOverrides->pluck('permission'));
            $hasRead = $hasRead || $this->hasReadPermissions($projectOverrides->pluck('permission'));

            foreach ($project->environments as $environment) {
                $envOverrides = $environment->permissionOverrides->where('user_id', $user->id);
                $hasWrite = $hasWrite || $this->hasWritePermissions($envOverrides->pluck('permission'));
                $hasRead = $hasRead || $this->hasReadPermissions($envOverrides->pluck('permission'));
            }
        }

        if ($hasWrite) {
            return PermissionLevel::EDITOR;
        }

        return PermissionLevel::BASE;
    }

    protected function hasWritePermissions(iterable $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($permission instanceof OrganizationPermission && in_array($permission, self::WRITE_PERMISSIONS, true)) {
                return true;
            }
        }

        return false;
    }

    protected function hasReadPermissions(iterable $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($permission instanceof OrganizationPermission && in_array($permission, [...self::READ_PERMISSIONS, ...self::WRITE_PERMISSIONS], true)) {
                return true;
            }
        }

        return false;
    }
}
