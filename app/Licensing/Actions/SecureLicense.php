<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\License;
use App\Licensing\Models\LicenseActivation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SecureLicense
{
    public function __construct(
        private LicenseKeyGenerator $licenseKeyGenerator,
        private RecordLicenseEvent $events,
        private EnforceLicenseRecoveryLimits $recoveryLimits
    ) {}

    /**
     * @return array{license: License, license_key: string, deactivated_count: int}
     */
    public function execute(License $license, string $source = 'account_secure'): array
    {
        if ($source === 'account_secure') {
            $this->recoveryLimits->ensureCanSecureLicense($license);
        }

        return DB::transaction(function () use ($license, $source): array {
            /** @var License $lockedLicense */
            $lockedLicense = License::query()
                ->whereKey($license->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $generatedKey = $this->licenseKeyGenerator->generate($lockedLicense->plan);

            $lockedLicense->forceFill([
                'license_key_hash' => $generatedKey['license_key_hash'],
                'encrypted_license_key' => $generatedKey['license_key'],
                'license_key_suffix' => $generatedKey['license_key_suffix'],
            ])->save();

            $deactivatedCount = 0;
            $deactivatedAt = now();

            /** @var Collection<int, LicenseActivation> $activations */
            $activations = LicenseActivation::query()
                ->whereBelongsTo($lockedLicense)
                ->whereNull('deactivated_at')
                ->lockForUpdate()
                ->oldest('id')
                ->get();

            $activations->each(function (LicenseActivation $activation) use ($lockedLicense, $source, $deactivatedAt, &$deactivatedCount): void {
                $activation->forceFill([
                    'deactivated_at' => $deactivatedAt,
                ])->save();

                $deactivatedCount++;

                $this->events->execute(
                    $lockedLicense,
                    'license.device_deactivated_by_owner',
                    [
                        'source' => $source,
                        'activation_id' => $activation->getKey(),
                        'platform' => $activation->platform,
                        'app_version' => $activation->app_version,
                    ],
                    $activation
                );
            });

            $this->events->execute(
                $lockedLicense,
                $source === 'admin' ? 'license.secured_by_admin' : 'license.secured_by_owner',
                [
                    'source' => $source,
                    'plan' => $lockedLicense->plan->value,
                    'deactivated_count' => $deactivatedCount,
                    'license_key_rotated' => true,
                ]
            );

            return [
                'license' => $lockedLicense->refresh(),
                'license_key' => $generatedKey['license_key'],
                'deactivated_count' => $deactivatedCount,
            ];
        });
    }
}
