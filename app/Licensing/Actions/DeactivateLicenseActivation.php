<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\LicenseActivation;
use Illuminate\Auth\AuthenticationException;

class DeactivateLicenseActivation
{
    public function __construct(
        private LicenseSecretHasher $hasher,
        private RecordLicenseEvent $events,
        private FlagSuspiciousLicenseActivity $flagSuspiciousActivity
    ) {}

    /**
     * @throws AuthenticationException
     */
    public function execute(?string $bearerToken): LicenseActivation
    {
        if ($bearerToken === null || $bearerToken === '') {
            $this->events->execute(null, 'license.deactivation_failed', [
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
            $this->events->execute(null, 'license.deactivation_failed', [
                'reason' => 'invalid_activation_token',
                'activation_token_hash' => $activationTokenHash,
            ]);

            $this->flagSuspiciousActivity->invalidActivationToken($activationTokenHash);

            throw new AuthenticationException('Invalid activation token.');
        }

        if ($activation->deactivated_at === null) {
            $activation->forceFill([
                'deactivated_at' => now(),
            ])->save();

            $this->events->execute(
                $activation->license,
                'license.deactivated',
                [
                    'platform' => $activation->platform,
                    'app_version' => $activation->app_version,
                ],
                $activation
            );
        }

        return $activation->refresh()->load('license');
    }
}
