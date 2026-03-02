<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Core\Http\Controllers\Controller;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Illuminate\Http\JsonResponse;

final class GetOrganizationPermissionMatrix extends Controller
{
    public function __invoke(Organization $organization): JsonResponse
    {
        $this->authorize('admin', $organization);

        $projectOverrides = array_map(
            static fn (OrganizationPermission $permission): string => $permission->value,
            OrganizationPermission::projectOverrides()
        );
        $environmentOverrides = array_map(
            static fn (OrganizationPermission $permission): string => $permission->value,
            OrganizationPermission::environmentOverrides()
        );

        $permissions = array_map(
            static fn (OrganizationPermission $permission): array => [
                'key' => $permission->value,
                'label' => $permission->label(),
                'group' => $permission->group(),
                'project_override_allowed' => in_array($permission->value, $projectOverrides, true),
                'environment_override_allowed' => in_array($permission->value, $environmentOverrides, true),
            ],
            OrganizationPermission::cases()
        );

        $roles = array_map(
            static function (OrganizationRole $role): array {
                $permissions = array_map(
                    static fn (OrganizationPermission $permission): string => $permission->value,
                    $role->permissions()
                );

                sort($permissions);

                return [
                    'key' => $role->value,
                    'label' => $role->label(),
                    'description' => $role->description(),
                    'permissions' => $permissions,
                ];
            },
            OrganizationRole::cases()
        );

        return response()->json([
            'data' => [
                'organization_id' => (string) $organization->id,
                'roles' => $roles,
                'permissions' => $permissions,
            ],
        ]);
    }
}
