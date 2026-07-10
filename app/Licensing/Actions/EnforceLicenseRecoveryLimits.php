<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\License;
use App\Licensing\Models\LicenseEvent;
use Illuminate\Validation\ValidationException;

class EnforceLicenseRecoveryLimits
{
    public function __construct(
        private RecordLicenseEvent $events,
        private FlagSuspiciousLicenseActivity $flagSuspiciousActivity
    ) {}

    public function ensureCanDeactivateDevice(License $license): void
    {
        $this->ensureWithinLimit(
            license: $license,
            reason: 'single_device_deactivation_limit_reached',
            eventTypes: ['license.device_deactivated_by_owner'],
            sources: ['account_single_device'],
            limit: (int) config('license.recovery.single_device_deactivations_per_window', 5),
            message: 'Device recovery limit reached. Contact support if you need help moving this license.'
        );
    }

    public function ensureCanDeactivateAllDevices(License $license): void
    {
        $this->ensureWithinLimit(
            license: $license,
            reason: 'all_device_deactivation_limit_reached',
            eventTypes: ['license.all_devices_deactivated_by_owner'],
            sources: ['account_all_devices'],
            limit: (int) config('license.recovery.all_device_deactivations_per_window', 2),
            message: 'Full device reset limit reached. Contact support if this license needs another reset.'
        );
    }

    public function ensureCanSecureLicense(License $license): void
    {
        $this->ensureWithinLimit(
            license: $license,
            reason: 'secure_license_reset_limit_reached',
            eventTypes: ['license.secured_by_owner'],
            sources: ['account_secure'],
            limit: (int) config('license.recovery.secure_license_resets_per_window', 2),
            message: 'Secure license reset limit reached. Contact support if this license may be compromised.'
        );
    }

    /**
     * @param  list<string>  $eventTypes
     * @param  list<string>  $sources
     */
    private function ensureWithinLimit(
        License $license,
        string $reason,
        array $eventTypes,
        array $sources,
        int $limit,
        string $message
    ): void {
        if ($limit < 1) {
            return;
        }

        $windowDays = max(1, (int) config('license.recovery.window_days', 30));
        $windowStartsAt = now()->subDays($windowDays);

        $attemptCount = LicenseEvent::query()
            ->where('license_id', $license->getKey())
            ->whereIn('type', $eventTypes)
            ->where(function ($query) use ($sources): void {
                foreach ($sources as $source) {
                    $query->orWhere('metadata->source', $source);
                }
            })
            ->where('created_at', '>=', $windowStartsAt)
            ->count();

        if ($attemptCount < $limit) {
            return;
        }

        $metadata = [
            'reason' => $reason,
            'attempt_count' => $attemptCount,
            'limit' => $limit,
            'window_days' => $windowDays,
        ];

        $this->events->execute($license, 'license.recovery_limit_reached', $metadata);
        $this->flagSuspiciousActivity->recoveryLimitReached($license, $metadata);

        throw ValidationException::withMessages([
            'license' => $message,
        ]);
    }
}
