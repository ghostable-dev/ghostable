<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Account\Models\User;
use App\Licensing\Models\License;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClaimGuestLicense
{
    /**
     * @throws AuthorizationException
     */
    public function execute(License $license, User $user): License
    {
        return DB::transaction(function () use ($license, $user): License {
            $license = License::query()->lockForUpdate()->findOrFail($license->getKey());

            if (! hash_equals(Str::lower($license->purchaser_email), Str::lower($user->email))) {
                throw new AuthorizationException('Sign in with the email address used during checkout to claim this license.');
            }

            if ($license->purchaser_user_id !== null && $license->purchaser_user_id !== $user->getKey()) {
                throw new AuthorizationException('This license has already been claimed by another account.');
            }

            $organization = Organization::query()->lockForUpdate()->findOrFail($license->organization_id);

            if ($organization->owner_id !== null && $organization->owner_id !== $user->getKey()) {
                throw new AuthorizationException('This license organization has already been claimed by another account.');
            }

            $organization->forceFill(['owner_id' => $user->getKey()])->save();

            if (! $organization->users()->whereKey($user->getKey())->exists()) {
                $user->organizationMembership()->assignToOrganization($organization, OrganizationRole::ADMIN);
            }

            if ($license->purchaser_user_id === null) {
                $license->forceFill(['purchaser_user_id' => $user->getKey()])->save();

                $license->events()->create([
                    'type' => 'license.claimed',
                    'metadata' => [
                        'user_id' => (string) $user->getKey(),
                        'source' => 'guest_license_claim',
                    ],
                ]);
            }

            return $license->fresh(['organization']);
        }, 3);
    }
}
