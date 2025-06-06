<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Contracts\SupportsOverrides;
use App\Team\Enums\TeamPermission;
use App\Team\Models\TeamPermissionOverride;

class CreatePermissionOverride
{
    public function handle(
        User $user,
        SupportsOverrides $target,
        TeamPermission $permission
    ): void {
        $override = new TeamPermissionOverride;
        $override->permission = $permission;
        $override->target()->associate($target);
        $override->user()->associate($user);
        $override->save();
    }
}
