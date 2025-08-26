<?php

namespace App\Organization\Builders;

use App\Account\Models\User;
use App\Organization\Enums\OrganizationPermission;
use Illuminate\Database\Eloquent\Builder;

class OrganizationPermissionOverrideBuilder extends Builder
{
    public function forUser(User $user): Builder
    {
        return $this->where('user_id', $user->id);
    }

    public function withPermission(OrganizationPermission $permission): Builder
    {
        return $this->where('permission', $permission->value);
    }
}
