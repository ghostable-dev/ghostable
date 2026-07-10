<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\License;
use App\Licensing\Models\LicenseActivation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class DeactivateLicenseDevice
{
    public function __construct(
        private RecordLicenseEvent $events,
        private EnforceLicenseRecoveryLimits $recoveryLimits
    ) {}

    public function execute(License $license, LicenseActivation $activation, string $source = 'account'): LicenseActivation
    {
        if ($activation->license_id !== $license->getKey()) {
            throw (new ModelNotFoundException)->setModel(LicenseActivation::class, [$activation->getKey()]);
        }

        if ($source === 'account_single_device') {
            $this->recoveryLimits->ensureCanDeactivateDevice($license);
        }

        return DB::transaction(function () use ($license, $activation, $source): LicenseActivation {
            /** @var LicenseActivation $lockedActivation */
            $lockedActivation = LicenseActivation::query()
                ->whereKey($activation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedActivation->deactivated_at === null) {
                $lockedActivation->forceFill([
                    'deactivated_at' => now(),
                ])->save();

                $this->events->execute(
                    $license,
                    'license.device_deactivated_by_owner',
                    [
                        'source' => $source,
                        'activation_id' => $lockedActivation->getKey(),
                        'platform' => $lockedActivation->platform,
                        'app_version' => $lockedActivation->app_version,
                    ],
                    $lockedActivation
                );
            }

            return $lockedActivation->refresh()->load('license');
        });
    }
}
