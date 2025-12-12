<?php

namespace App\Organization\Actions;

use App\Account\Models\User;
use App\Billing\Enums\BillingPolicy;
use App\Billing\Enums\Plan;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Events\OrganizationCreated;
use App\Organization\Models\Organization;

class CreateOrganization
{
    public static function handle(string $name, User $owner, ?Plan $planOverride = null): Organization
    {
        $organization = new Organization;
        $organization->name = $name;
        $organization->owner()->associate($owner);

        if ($planOverride) {
            $organization->billing_policy = BillingPolicy::MANUAL_OVERRIDE;
            $organization->plan_override = $planOverride;
        }

        $organization->save();

        $owner->organizationMembership()->assignToOrganization(organization: $organization, role: OrganizationRole::ADMIN);

        OrganizationCreated::dispatch($organization, $owner);

        return $organization;
    }
}
