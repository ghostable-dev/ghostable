<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Models\License;
use App\Licensing\Models\LicenseActivation;
use App\Licensing\Models\LicenseEvent;

class RecordLicenseEvent
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        ?License $license,
        string $type,
        array $metadata = [],
        ?LicenseActivation $activation = null
    ): LicenseEvent {
        return LicenseEvent::query()->create([
            'license_id' => $license?->getKey(),
            'license_activation_id' => $activation?->getKey(),
            'type' => $type,
            'metadata' => $this->sanitizeMetadata($metadata),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        unset($metadata['license_key'], $metadata['encrypted_license_key'], $metadata['activation_token']);

        return $metadata;
    }
}
