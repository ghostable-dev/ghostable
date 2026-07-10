<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\LicenseActivation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

class ValidateLicenseActivation
{
    public function __construct(
        private LicenseSecretHasher $hasher,
        private RecordLicenseEvent $events,
        private FlagSuspiciousLicenseActivity $flagSuspiciousActivity
    ) {}

    /**
     * @param  array{activation_id: string, machine_fingerprint: string, app_version: string}  $data
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function execute(?string $bearerToken, array $data): LicenseActivation
    {
        $activation = $this->findActivation($bearerToken);

        if ($activation->getKey() !== $data['activation_id']) {
            $this->recordFailure($activation, 'activation_id_mismatch', [
                'submitted_activation_id' => $data['activation_id'],
            ]);

            throw new AuthorizationException('The activation does not match the token.');
        }

        if ($activation->deactivated_at !== null) {
            $this->recordFailure($activation, 'activation_deactivated');

            throw new AuthorizationException('The activation has been deactivated.');
        }

        $submittedMachineFingerprintHash = $this->hasher->hashMachineFingerprint($data['machine_fingerprint']);

        if (! hash_equals($activation->machine_fingerprint_hash, $submittedMachineFingerprintHash)) {
            $this->recordFailure($activation, 'machine_fingerprint_mismatch', [
                'submitted_machine_fingerprint_hash' => $submittedMachineFingerprintHash,
            ]);

            throw new AuthorizationException('The machine fingerprint does not match the activation.');
        }

        if (! $activation->license->isUsable()) {
            $this->recordFailure($activation, 'license_not_usable', [
                'license_status' => $activation->license->status->value,
            ]);

            throw new AuthorizationException('The license is not active.');
        }

        $activation->forceFill([
            'app_version' => $data['app_version'],
            'last_validated_at' => now(),
        ])->save();

        $this->events->execute(
            $activation->license,
            'license.validated',
            [
                'app_version' => $activation->app_version,
            ],
            $activation
        );

        return $activation->refresh()->load('license');
    }

    /**
     * @throws AuthenticationException
     */
    private function findActivation(?string $bearerToken): LicenseActivation
    {
        if ($bearerToken === null || $bearerToken === '') {
            $this->events->execute(null, 'license.validation_failed', [
                'reason' => 'missing_activation_token',
            ]);

            throw new AuthenticationException('Missing activation token.');
        }

        $activationTokenHash = $this->hasher->hashActivationToken($bearerToken);
        $activation = LicenseActivation::query()
            ->with('license')
            ->where('activation_token_hash', $activationTokenHash)
            ->first();

        if (! $activation instanceof LicenseActivation) {
            $this->events->execute(null, 'license.validation_failed', [
                'reason' => 'invalid_activation_token',
                'activation_token_hash' => $activationTokenHash,
            ]);

            $this->flagSuspiciousActivity->invalidActivationToken($activationTokenHash);

            throw new AuthenticationException('Invalid activation token.');
        }

        return $activation;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordFailure(LicenseActivation $activation, string $reason, array $metadata = []): void
    {
        $this->events->execute(
            $activation->license,
            'license.validation_failed',
            [
                'reason' => $reason,
                'activation_id' => $activation->getKey(),
                'app_version' => $activation->app_version,
                ...$metadata,
            ],
            $activation
        );

        if ($reason === 'machine_fingerprint_mismatch' && isset($metadata['submitted_machine_fingerprint_hash'])) {
            $this->flagSuspiciousActivity->machineFingerprintMismatch(
                $activation,
                (string) $metadata['submitted_machine_fingerprint_hash']
            );
        }
    }
}
