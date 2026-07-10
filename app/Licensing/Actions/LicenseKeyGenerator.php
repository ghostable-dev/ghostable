<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Enums\LicensePlan;
use App\Licensing\Models\License;
use Illuminate\Support\Str;

class LicenseKeyGenerator
{
    public function __construct(private LicenseSecretHasher $hasher) {}

    /**
     * @return array{license_key: string, license_key_hash: string, license_key_suffix: string}
     */
    public function generate(LicensePlan $plan): array
    {
        do {
            $licenseKey = $this->makeLicenseKey($plan);
            $licenseKeyHash = $this->hasher->hashLicenseKey($licenseKey);
        } while (License::query()->where('license_key_hash', $licenseKeyHash)->exists());

        return [
            'license_key' => $licenseKey,
            'license_key_hash' => $licenseKeyHash,
            'license_key_suffix' => Str::of($licenseKey)->afterLast('-')->toString(),
        ];
    }

    private function makeLicenseKey(LicensePlan $plan): string
    {
        $groups = [];

        for ($index = 0; $index < 4; $index++) {
            $groups[] = Str::upper(Str::random(4));
        }

        return 'GHST-'.$plan->keyPrefix().'-'.implode('-', $groups);
    }
}
