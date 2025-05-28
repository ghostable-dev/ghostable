<?php

namespace App\Environment\Policies;

use App\Account\Enums\Permission;
use App\Account\Models\User;
use App\Environment\Models\Environment;

class EnvironmentPolicy
{
    public function view(User $user, Environment $env): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::EnvPull,
            team: $env->project->team
        );
    }

    public function update(User $user, Environment $env): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::EnvUpdate,
            team: $env->project->team
        );
    }

    public function delete(User $user, Environment $env): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::EnvDelete,
            team: $env->project->team
        );
    }

    public function push(User $user, Environment $env): bool
    {
        return $user->hasTeamPermission(
            permission: Permission::EnvPush,
            team: $env->project->team
        );
    }
}