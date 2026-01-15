<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Auth\Actions\LogAccountSecurityActivity;
use App\Organization\Contracts\SupportsOverrides;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\OrganizationPermissionOverride;

class CreatePermissionOverride
{
    public function handle(
        User $user,
        SupportsOverrides $target,
        OrganizationPermission $permission,
        ?User $actor = null
    ): void {
        $override = new OrganizationPermissionOverride;
        $override->permission = $permission;
        $override->target()->associate($target);
        $override->user()->associate($user);
        $override->save();

        app(LogAccountSecurityActivity::class)->permissionOverrideGranted(
            member: $user,
            permission: $permission->value,
            context: [
                'target' => [
                    'type' => class_basename($target),
                    'id' => (string) $target->id,
                    'name' => data_get($target, 'name'),
                ],
            ],
            actor: $actor
        );
    }
}
