<?php

namespace App\Environment\Policies;

use App\Account\Models\User;
use App\Environment\Models\Environment;

class EnvironmentPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Environment $env): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Environment $env): bool
    {
        return $env->project->team->users()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function delete(User $user, Environment $env): bool
    {
        return false;
    }

    public function restore(User $user, Environment $env): bool
    {
        return false;
    }

    public function forceDelete(User $user, Environment $env): bool
    {
        return false;
    }
}
