<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Account\Models\User;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Models\License;
use App\Licensing\Notifications\ManualLicenseGrantedNotification;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreateManualLicenseGrant
{
    public function __construct(
        private CreateLicense $licenses,
        private RecordLicenseEvent $events,
    ) {}

    /**
     * @return array{license: License, license_key: string}
     */
    public function execute(
        Organization $organization,
        LicensePlan|string $plan,
        string $purchaserEmail,
        ?User $purchaser = null,
        ?User $actor = null,
        ?string $note = null,
    ): array {
        $licensePlan = $plan instanceof LicensePlan
            ? $plan
            : LicensePlan::from($plan);

        if ($purchaser instanceof User && ! $organization->users()->whereKey($purchaser->getKey())->exists()) {
            throw new InvalidArgumentException('The purchaser must belong to the organization receiving the license.');
        }

        $normalizedNote = Str::of((string) $note)
            ->trim()
            ->limit(1000, '')
            ->toString();
        $normalizedPurchaserEmail = Str::of($purchaserEmail)->trim()->lower()->toString();

        $metadata = [
            'source' => 'filament_manual_grant',
            'actor_user_id' => $actor?->getKey(),
            'actor_email' => $actor?->email,
        ];

        if (filled($normalizedNote)) {
            $metadata['note'] = $normalizedNote;
        }

        $result = $this->licenses->execute([
            'organization' => $organization,
            'purchaser_user' => $purchaser,
            'plan' => $licensePlan,
            'email' => $normalizedPurchaserEmail,
            'provider' => 'manual',
            'provider_metadata' => $metadata,
        ]);

        /** @var License $license */
        $license = $result['license'];

        $this->events->execute($license, 'license.manual_grant_created', [
            'source' => 'filament_manual_grant',
            'actor_user_id' => $actor?->getKey(),
            'actor_email' => $actor?->email,
            'organization_id' => $organization->getKey(),
            'purchaser_user_id' => $purchaser?->getKey(),
            'purchaser_email' => $normalizedPurchaserEmail,
            'plan' => $licensePlan->value,
            'note' => filled($normalizedNote) ? $normalizedNote : null,
        ]);

        Notification::route('mail', $normalizedPurchaserEmail)
            ->notify(new ManualLicenseGrantedNotification($license));

        $this->events->execute($license, 'license.manual_grant_email_dispatched', [
            'source' => 'filament_manual_grant',
            'actor_user_id' => $actor?->getKey(),
            'actor_email' => $actor?->email,
            'organization_id' => $organization->getKey(),
            'recipient_email' => $normalizedPurchaserEmail,
        ]);

        return [
            'license' => $license,
            'license_key' => (string) $result['license_key'],
        ];
    }
}
