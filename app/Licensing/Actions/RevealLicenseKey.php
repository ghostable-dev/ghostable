<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\License;

class RevealLicenseKey
{
    public function __construct(private RecordLicenseEvent $events) {}

    public function execute(License $license, string $source = 'account'): ?string
    {
        $licenseKey = $license->encrypted_license_key;

        $this->events->execute(
            $license,
            $licenseKey === null ? 'license.key_reveal_unavailable' : 'license.key_revealed',
            [
                'source' => $source,
                'license_key_suffix' => $license->license_key_suffix,
            ]
        );

        return $licenseKey;
    }
}
