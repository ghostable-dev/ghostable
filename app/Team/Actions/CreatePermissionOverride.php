<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Project\Models\Project;
use App\Team\Enums\TeamPermission;
use App\Team\Models\TeamPermissionOverride;

class CreatePermissionOverride
{
    public function handle(
        User $user, 
        Project|Environment $target, 
        TeamPermission $permission
    ): void
    {
        $override = new TeamPermissionOverride();
        $override->permission = $permission;
        $override->target()->associate($target);
        $override->user()->associate($user);
        $override->save();
    }
}
