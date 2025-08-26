<?php

namespace App\Organization\Actions;

use App\Organization\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class SwitchToOrganization
{
    public static function handle(Organization $organization): void
    {
        if (! $organization->users->contains(Auth::user())) {
            throw new AuthorizationException('You are not a member of this organization.');
        }

        session()->put('current_organization_id', $organization->id);
    }
}
