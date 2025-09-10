<?php

namespace App\Organization\Contracts;

use App\Account\Models\User;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface SupportsOverrides
{
    public function owningOrganization(): Organization;

    public function isRestricted(): bool;

    public function permissionOverrides(): MorphMany;

    public function userHasOverride(User $user, OrganizationPermission $permission): bool;
}
