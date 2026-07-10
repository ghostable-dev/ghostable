<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\License;
use App\Licensing\Models\LicenseActivation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActivateLicense
{
    public function __construct(
        private LicenseSecretHasher $hasher,
        private RecordLicenseEvent $events,
        private FlagSuspiciousLicenseActivity $flagSuspiciousActivity
    ) {}

    /**
     * @param  array{license_key: string, machine_fingerprint: string, machine_name?: ?string, platform: string, app_version: string}  $data
     * @return array{activation: LicenseActivation, activation_token: string}
     */
    public function execute(array $data): array
    {
        $licenseKeyHash = $this->hasher->hashLicenseKey($data['license_key']);

        $result = DB::transaction(function () use ($data, $licenseKeyHash): array {
            /** @var License|null $license */
            $license = License::query()
                ->where('license_key_hash', $licenseKeyHash)
                ->lockForUpdate()
                ->first();

            if (! $license instanceof License) {
                return [
                    'failure' => [
                        'message' => 'The license key is invalid.',
                        'metadata' => [
                            'reason' => 'invalid_license_key',
                            'license_key_hash' => $licenseKeyHash,
                            'platform' => $data['platform'],
                            'app_version' => $data['app_version'],
                        ],
                    ],
                ];
            }

            if (! $license->isUsable()) {
                return [
                    'failure' => [
                        'license' => $license,
                        'message' => 'The license is not active.',
                        'metadata' => [
                            'reason' => 'license_not_usable',
                            'license_status' => $license->status->value,
                            'platform' => $data['platform'],
                            'app_version' => $data['app_version'],
                        ],
                    ],
                ];
            }

            $fingerprintHash = $this->hasher->hashMachineFingerprint($data['machine_fingerprint']);
            /** @var LicenseActivation|null $activation */
            $activation = $license->activeActivations()
                ->where('machine_fingerprint_hash', $fingerprintHash)
                ->first();

            if (! $activation instanceof LicenseActivation) {
                $activeActivationCount = $license->activeActivations()->count();

                if ($activeActivationCount >= $license->activation_limit) {
                    return [
                        'failure' => [
                            'license' => $license,
                            'message' => 'The license activation limit has been reached.',
                            'metadata' => [
                                'reason' => 'activation_limit_reached',
                                'activation_limit' => $license->activation_limit,
                                'active_activations_count' => $activeActivationCount,
                                'platform' => $data['platform'],
                                'app_version' => $data['app_version'],
                            ],
                        ],
                    ];
                }

                /** @var LicenseActivation $activation */
                $activation = $license->activations()->create([
                    'activation_token_hash' => $this->hasher->hashActivationToken('pending:'.Str::uuid()),
                    'machine_fingerprint_hash' => $fingerprintHash,
                    'machine_name' => $data['machine_name'] ?? null,
                    'platform' => $data['platform'],
                    'app_version' => $data['app_version'],
                ]);
            }

            [$activationToken, $activationTokenHash] = $this->uniqueActivationToken();

            $activation->forceFill([
                'activation_token_hash' => $activationTokenHash,
                'machine_name' => $data['machine_name'] ?? $activation->machine_name,
                'platform' => $data['platform'],
                'app_version' => $data['app_version'],
                'deactivated_at' => null,
            ])->save();

            $this->events->execute(
                $license,
                'license.activated',
                [
                    'platform' => $activation->platform,
                    'app_version' => $activation->app_version,
                ],
                $activation
            );

            return [
                'activation' => $activation->refresh()->load('license'),
                'activation_token' => $activationToken,
            ];
        });

        if (isset($result['failure'])) {
            /** @var array{license?: License, message: string, metadata: array<string, mixed>} $failure */
            $failure = $result['failure'];

            $this->events->execute(
                $failure['license'] ?? null,
                'license.activation_failed',
                $failure['metadata']
            );

            $this->flagSuspiciousFailure($failure['license'] ?? null, $failure['metadata']);

            throw ValidationException::withMessages([
                'license_key' => $failure['message'],
            ]);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function flagSuspiciousFailure(?License $license, array $metadata): void
    {
        if (! $license instanceof License) {
            return;
        }

        if (($metadata['reason'] ?? null) === 'activation_limit_reached') {
            $this->flagSuspiciousActivity->activationLimitReached($license);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function uniqueActivationToken(): array
    {
        do {
            $activationToken = 'ghst_act_'.Str::random(64);
            $activationTokenHash = $this->hasher->hashActivationToken($activationToken);
        } while (LicenseActivation::query()->where('activation_token_hash', $activationTokenHash)->exists());

        return [$activationToken, $activationTokenHash];
    }
}
