<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\LicenseActivation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

class CheckForLicenseUpdate
{
    public function __construct(private LicenseSecretHasher $hasher) {}

    /**
     * @param  array{platform: string, version: string}  $data
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function execute(?string $bearerToken, array $data): array
    {
        $activation = $this->findActivation($bearerToken);

        if ($activation->deactivated_at !== null) {
            throw new AuthorizationException('The activation has been deactivated.');
        }

        if (! $activation->license->isUsable()) {
            throw new AuthorizationException('The license is not active.');
        }

        $latestVersion = (string) config('license.updates.latest_version', '0.1.0');
        $isEligible = $activation->license->isUpdateEligible();

        return [
            'status' => $isEligible ? 'eligible' : 'renewal_required',
            'eligible' => $isEligible,
            'latest_version' => $latestVersion,
            'update_available' => $isEligible && version_compare($data['version'], $latestVersion, '<'),
            'platform' => $data['platform'],
            'current_version' => $data['version'],
            'updates_until' => $activation->license->updates_until?->toIso8601String(),
        ];
    }

    /**
     * @throws AuthenticationException
     */
    private function findActivation(?string $bearerToken): LicenseActivation
    {
        if ($bearerToken === null || $bearerToken === '') {
            throw new AuthenticationException('Missing activation token.');
        }

        $activation = LicenseActivation::query()
            ->with('license')
            ->where('activation_token_hash', $this->hasher->hashActivationToken($bearerToken))
            ->first();

        if (! $activation instanceof LicenseActivation) {
            throw new AuthenticationException('Invalid activation token.');
        }

        return $activation;
    }
}
