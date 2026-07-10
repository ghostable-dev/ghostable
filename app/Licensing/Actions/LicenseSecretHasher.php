<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use Illuminate\Support\Str;

class LicenseSecretHasher
{
    public function hashLicenseKey(string $licenseKey): string
    {
        return $this->hash($this->normalizeLicenseKey($licenseKey));
    }

    public function hashActivationToken(string $activationToken): string
    {
        return $this->hash($activationToken);
    }

    public function hashMachineFingerprint(string $machineFingerprint): string
    {
        return $this->hash($machineFingerprint);
    }

    public function normalizeLicenseKey(string $licenseKey): string
    {
        return Str::of($licenseKey)->trim()->upper()->toString();
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
