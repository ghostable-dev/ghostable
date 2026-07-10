<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\License;
use App\Licensing\Models\LicenseActivation;
use Illuminate\Database\Eloquent\Collection;

class DeactivateAllLicenseDevices
{
    public function __construct(
        private DeactivateLicenseDevice $deactivateLicenseDevice,
        private RecordLicenseEvent $events,
        private EnforceLicenseRecoveryLimits $recoveryLimits
    ) {}

    public function execute(License $license, string $source = 'account'): int
    {
        if ($source === 'account_all_devices') {
            $this->recoveryLimits->ensureCanDeactivateAllDevices($license);
        }

        $deactivatedCount = 0;

        /** @var Collection<int, LicenseActivation> $activations */
        $activations = $license->activeActivations()
            ->oldest('id')
            ->get();

        $activations->each(function (LicenseActivation $activation) use ($license, $source, &$deactivatedCount): void {
            $this->deactivateLicenseDevice->execute($license, $activation, $source);
            $deactivatedCount++;
        });

        $this->events->execute($license, 'license.all_devices_deactivated_by_owner', [
            'source' => $source,
            'deactivated_count' => $deactivatedCount,
        ]);

        return $deactivatedCount;
    }
}
