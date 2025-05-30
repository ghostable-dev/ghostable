<?php

namespace App\Environment\Policies;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Team\Enums\TeamPermission;

class EnvironmentPolicy
{
    public function view(User $user, Environment $env): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::EnvPull,
            team: $env->project->team
        );
    }

    public function update(User $user, Environment $env): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::EnvUpdate,
            team: $env->project->team
        );
    }

    public function delete(User $user, Environment $env): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::EnvDelete,
            team: $env->project->team
        );
    }

    public function push(User $user, Environment $env): bool
    {
        return $user->hasTeamPermission(
            permission: TeamPermission::EnvPush,
            team: $env->project->team
        );
    }
}
