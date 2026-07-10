<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\License;
use Illuminate\Support\Facades\DB;

class RotateLicenseKey
{
    public function __construct(private LicenseKeyGenerator $licenseKeyGenerator) {}

    /**
     * @return array{license: License, license_key: string}
     */
    public function execute(License $license, string $source = 'account'): array
    {
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

            $lockedLicense->events()->create([
                'type' => 'license.key_rotated',
                'metadata' => [
                    'plan' => $lockedLicense->plan->value,
                    'source' => $source,
                    'active_device_slots_reset' => false,
                    'existing_activations_kept' => true,
                ],
            ]);

            return [
                'license' => $lockedLicense->refresh(),
                'license_key' => $generatedKey['license_key'],
            ];
        });
    }
}
