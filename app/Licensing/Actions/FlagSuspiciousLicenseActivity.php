<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\License;
use App\Licensing\Models\LicenseActivation;
use App\Licensing\Models\LicenseEvent;
use DateTimeInterface;

class FlagSuspiciousLicenseActivity
{
    private const FAILURE_THRESHOLD = 3;

    public function __construct(private RecordLicenseEvent $events) {}

    public function activationLimitReached(License $license): void
    {
        $windowStartsAt = now()->subDay();
        $failureCount = LicenseEvent::query()
            ->where('license_id', $license->getKey())
            ->where('type', 'license.activation_failed')
            ->where('metadata->reason', 'activation_limit_reached')
            ->where('created_at', '>=', $windowStartsAt)
            ->count();

        if ($failureCount < self::FAILURE_THRESHOLD) {
            return;
        }

        $this->recordLicenseFlagOnce(
            $license,
            'repeated_activation_limit_failures',
            [
                'reason' => 'repeated_activation_limit_failures',
                'failure_count' => $failureCount,
                'window_minutes' => 1440,
                'activation_limit' => $license->activation_limit,
            ],
            $windowStartsAt
        );
    }

    public function machineFingerprintMismatch(LicenseActivation $activation, string $submittedMachineFingerprintHash): void
    {
        $activation->loadMissing('license');

        $windowStartsAt = now()->subDay();
        $failureCount = LicenseEvent::query()
            ->where('license_id', $activation->license_id)
            ->where('license_activation_id', $activation->getKey())
            ->where('type', 'license.validation_failed')
            ->where('metadata->reason', 'machine_fingerprint_mismatch')
            ->where('created_at', '>=', $windowStartsAt)
            ->count();

        if ($failureCount < self::FAILURE_THRESHOLD) {
            return;
        }

        $this->recordLicenseFlagOnce(
            $activation->license,
            'repeated_machine_fingerprint_mismatches',
            [
                'reason' => 'repeated_machine_fingerprint_mismatches',
                'activation_id' => $activation->getKey(),
                'submitted_machine_fingerprint_hash' => $submittedMachineFingerprintHash,
                'failure_count' => $failureCount,
                'window_minutes' => 1440,
            ],
            $windowStartsAt,
            $activation
        );
    }

    public function invalidActivationToken(string $activationTokenHash): void
    {
        $windowStartsAt = now()->subHour();
        $failureCount = LicenseEvent::query()
            ->whereNull('license_id')
            ->whereIn('type', ['license.validation_failed', 'license.deactivation_failed'])
            ->where('metadata->reason', 'invalid_activation_token')
            ->where('metadata->activation_token_hash', $activationTokenHash)
            ->where('created_at', '>=', $windowStartsAt)
            ->count();

        if ($failureCount < self::FAILURE_THRESHOLD) {
            return;
        }

        if ($this->flagAlreadyExists(null, 'repeated_invalid_activation_token_attempts', $windowStartsAt, $activationTokenHash)) {
            return;
        }

        $this->events->execute(null, 'license.suspicious_activity_flagged', [
            'reason' => 'repeated_invalid_activation_token_attempts',
            'activation_token_hash' => $activationTokenHash,
            'failure_count' => $failureCount,
            'window_minutes' => 60,
        ]);
    }

    /**
     * @param  array{reason: string, attempt_count: int, limit: int, window_days: int}  $metadata
     */
    public function recoveryLimitReached(License $license, array $metadata): void
    {
        $windowStartsAt = now()->subDays($metadata['window_days']);

        $this->recordLicenseFlagOnce(
            $license,
            $metadata['reason'],
            [
                'reason' => $metadata['reason'],
                'attempt_count' => $metadata['attempt_count'],
                'limit' => $metadata['limit'],
                'window_days' => $metadata['window_days'],
            ],
            $windowStartsAt
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordLicenseFlagOnce(
        License $license,
        string $reason,
        array $metadata,
        DateTimeInterface $windowStartsAt,
        ?LicenseActivation $activation = null
    ): void {
        if ($this->flagAlreadyExists($license, $reason, $windowStartsAt)) {
            return;
        }

        $this->events->execute($license, 'license.suspicious_activity_flagged', $metadata, $activation);
    }

    private function flagAlreadyExists(
        ?License $license,
        string $reason,
        DateTimeInterface $windowStartsAt,
        ?string $activationTokenHash = null
    ): bool {
        $query = LicenseEvent::query()
            ->where('type', 'license.suspicious_activity_flagged')
            ->where('metadata->reason', $reason)
            ->where('created_at', '>=', $windowStartsAt);

        if ($license instanceof License) {
            $query->where('license_id', $license->getKey());
        } else {
            $query->whereNull('license_id');
        }

        if ($activationTokenHash !== null) {
            $query->where('metadata->activation_token_hash', $activationTokenHash);
        }

        return $query->exists();
    }
}
