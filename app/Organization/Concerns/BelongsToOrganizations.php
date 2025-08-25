<?php

namespace App\Organization\Concerns;

use App\Organization\Actions\SwitchToOrganization;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationUser;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait BelongsToOrganizations
{
    public function currentOrganization(): ?Organization
    {
        return once(function () {
            $organization = $this->organizations()
                ->where('organizations.id', $organizationId = session('current_organization_id'))
                ->first();

            if (! $organization) {
                $organization = $this->personalOrganization();
                app(SwitchToOrganization::class)->handle($organization);
            }

            return $organization;
        }, "currentOrganization:{$this->id}");
    }

    public function personalOrganization(): Organization
    {
        return $this->ownedOrganizations()->personal()->first();
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->using(OrganizationUser::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_id');
    }

    public function isOrganizationAdmin(Organization $organization): bool
    {
        return $this->organizationMembership()->hasOrganizationRole(
            role: OrganizationRole::ADMIN,
            organization: $organization
        );
    }
}
