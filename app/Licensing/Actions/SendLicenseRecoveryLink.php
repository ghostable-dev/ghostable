<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Licensing\Notifications\LicenseRecoveryNotification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

class SendLicenseRecoveryLink
{
    public function __construct(
        private readonly FindRecoverableLicenses $recoverableLicenses,
        private readonly RecordLicenseEvent $events,
    ) {}

    public function execute(string $email): void
    {
        $licenses = $this->recoverableLicenses->execute($email);

        if ($licenses->isEmpty()) {
            return;
        }

        $expiresInMinutes = max(1, (int) config('license.recovery.link_ttl_minutes'));
        $managementUrl = URL::temporarySignedRoute(
            'licenses.manage.verify',
            now()->addMinutes($expiresInMinutes),
            ['email' => Crypt::encryptString($email)],
        );

        Notification::route('mail', $email)->notify(new LicenseRecoveryNotification(
            managementUrl: $managementUrl,
            licenseCount: $licenses->count(),
            expiresInMinutes: $expiresInMinutes,
        ));

        foreach ($licenses as $license) {
            $this->events->execute($license, 'license.management_link_requested', [
                'source' => 'public_license_management',
                'recipient_email' => $email,
            ]);
        }
    }
}
