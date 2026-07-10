<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\LicenseActivation;

class LicenseEntitlementPresenter
{
    public function __construct(private SignLicenseEntitlement $signLicenseEntitlement) {}

    /**
     * @return array<string, mixed>
     */
    public function present(LicenseActivation $activation): array
    {
        $activation->loadMissing('license.organization');
        $license = $activation->license;
        $issuedAt = now();

        return [
            'schema_version' => 1,
            'license' => [
                'id' => (string) $license->getKey(),
                'organization_id' => (string) $license->organization_id,
                'status' => $license->status->value,
                'plan' => $license->plan->value,
                'features' => $license->features(),
                'seat_count' => $license->seat_count,
                'activation_limit' => $license->activation_limit,
                'updates_until' => $license->updates_until?->toIso8601String(),
                'expires_at' => $license->expires_at?->toIso8601String(),
            ],
            'organization' => [
                'id' => (string) $license->organization_id,
                'name' => $license->organization->name,
            ],
            'activation' => [
                'id' => (string) $activation->getKey(),
                'status' => $activation->status(),
                'machine_fingerprint_hash' => $activation->machine_fingerprint_hash,
                'machine_name' => $activation->machine_name,
                'platform' => $activation->platform,
                'app_version' => $activation->app_version,
                'last_validated_at' => $activation->last_validated_at?->toIso8601String(),
                'deactivated_at' => $activation->deactivated_at?->toIso8601String(),
            ],
            'issued_at' => $issuedAt->toIso8601String(),
            'valid_until' => $issuedAt
                ->addMinutes((int) config('license.entitlements.ttl_minutes', 10080))
                ->toIso8601String(),
        ];
    }

    /**
     * @return array{payload: array<string, mixed>, signature: string, key_id: string, algorithm: string}
     */
    public function signed(LicenseActivation $activation): array
    {
        return $this->signLicenseEntitlement->execute($this->present($activation));
    }
}
