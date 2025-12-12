<?php

declare(strict_types=1);

namespace App\Integration\Integrations\Vanta\Support;

use App\Account\Models\User;
use App\Integration\Integrations\Vanta\Enums\AuthMethod;
use App\Integration\Integrations\Vanta\Enums\MfaMethod;
use App\Integration\Integrations\Vanta\Enums\ResourceStatus;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\URL;

class UserResourceMapper
{
    public function map(User $user, Organization $organization, PermissionLevelResolver $permissionResolver): array
    {
        $role = $user->pivot?->role;
        $resolvedRole = null;

        if ($role instanceof OrganizationRole) {
            $resolvedRole = $role->value;
        } elseif (is_string($role) && $role !== '') {
            $resolvedRole = $role;
        }

        $createdAt = $user->pivot?->created_at ?? $user->created_at;
        $mfaEnabled = $user->hasEnabledTwoFactorAuthentication();
        $mfaMethods = $mfaEnabled ? [MfaMethod::OTP->value] : [MfaMethod::DISABLED->value];
        $permissionLevel = $permissionResolver->resolve($user, $organization, $resolvedRole);

        $payload = [
            'uniqueId' => (string) $user->id,
            'displayName' => $user->name ?: $user->email,
            'externalUrl' => URL::route('organization.settings.members').'#user-'.$user->id,
            'fullName' => $user->name ?: $user->email,
            'accountName' => $user->email,
            'email' => $user->email,
            'permissionLevel' => $permissionLevel->value,
            'createdTimestamp' => optional($createdAt)->toIso8601String(),
            'status' => $user->trashed() ? ResourceStatus::DEACTIVATED->value : ResourceStatus::ACTIVE->value,
            'mfaEnabled' => $mfaEnabled,
            'mfaMethods' => $mfaMethods,
            'authMethod' => AuthMethod::PASSWORD->value,
            'roleDescription' => $this->mapRoleDescription($resolvedRole),
        ];

        return array_filter($payload, fn ($value) => $value !== null);
    }

    protected function mapRoleDescription(?string $role): ?string
    {
        if (! $role) {
            return null;
        }

        $enum = OrganizationRole::tryFrom($role);

        return $enum?->description();
    }
}
