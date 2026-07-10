<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Enums\LicenseStatus;
use App\Licensing\Models\License;

class UpdateLicenseStatus
{
    public function __construct(private RecordLicenseEvent $events) {}

    public function execute(License $license, LicenseStatus $status, string $source = 'admin'): License
    {
        $previous = $license->status;

        $license->forceFill([
            'status' => $status,
        ])->save();

        $this->events->execute($license->refresh(), 'license.status_changed', [
            'source' => $source,
            'previous' => $previous->value,
            'current' => $license->status->value,
        ]);

        return $license;
    }
}
