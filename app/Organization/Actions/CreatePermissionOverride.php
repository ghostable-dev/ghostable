<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Organization\Contracts\SupportsOverrides;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\OrganizationPermissionOverride;

class CreatePermissionOverride
{
    public function handle(
        User $user,
        SupportsOverrides $target,
        OrganizationPermission $permission
    ): void {
        $override = new OrganizationPermissionOverride;
        $override->permission = $permission;
        $override->target()->associate($target);
        $override->user()->associate($user);
        $override->save();
    }
}
