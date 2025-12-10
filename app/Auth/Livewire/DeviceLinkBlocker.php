<?php

namespace App\Auth\Livewire;

use App\Organization\Enums\OrganizationRole;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DeviceLinkBlocker extends Component
{
    #[Computed]
    public function showDeviceReminderBanner(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $currentOrganization = $user->currentOrganization();

        if (! $currentOrganization) {
            return false;
        }

        $organizationHasLinkedDevice = $currentOrganization->users()
            ->whereHas('devices', fn ($query) => $query
                ->where('active', true)
                ->whereNull('revoked_at'))
            ->exists();

        $userHasLinkedDevice = $user->devices()
            ->where('active', true)
            ->whereNull('revoked_at')
            ->exists();

        $role = $currentOrganization->pivot?->role
            ?? $user->organizationMembership()->getMembershipForOrganization($currentOrganization)?->pivot?->role;

        $role = is_string($role)
            ? OrganizationRole::tryFrom($role)
            : $role;

        $isExemptRole = in_array($role, [
            OrganizationRole::BILLING_ONLY,
            OrganizationRole::AUDITOR,
        ], true);

        return ! $userHasLinkedDevice
            && $organizationHasLinkedDevice
            && ! $isExemptRole;
    }

    #[Computed]
    public function requiresDeviceLink(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $currentOrganization = $user->currentOrganization();

        $organizationHasLinkedDevice = $currentOrganization?->users()
            ->whereHas('devices', fn ($query) => $query
                ->where('active', true)
                ->whereNull('revoked_at'))
            ->exists() ?? false;

        $userHasLinkedDevice = $user->devices()
            ->where('active', true)
            ->whereNull('revoked_at')
            ->exists();

        return ! $userHasLinkedDevice && ! $organizationHasLinkedDevice;
    }

    public function render()
    {
        return view('livewire.auth.device-link-blocker');
    }
}
