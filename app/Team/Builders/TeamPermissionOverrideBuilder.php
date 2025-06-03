<?php

namespace App\Team\Builders;

use App\Account\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TeamPermissionOverrideBuilder extends Builder
{
    public function forUser(User $user): Builder
    {
        return $this->where('user_id', $user->id);
    }
}
