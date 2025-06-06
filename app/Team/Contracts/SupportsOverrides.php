<?php

namespace App\Team\Contracts;

use App\Account\Models\User;
use App\Team\Enums\TeamPermission;
use App\Team\Models\Team;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface SupportsOverrides
{
    public function owningTeam(): Team;

    public function isRestricted(): bool;

    public function permissionOverrides(): MorphMany;

    public function userHasOverride(User $user, TeamPermission $permission): bool;
}
