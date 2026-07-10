<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Account\Models\User;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Enums\LicenseStatus;
use App\Licensing\Models\License;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class CreateLicense
{
    public function __construct(private LicenseKeyGenerator $licenseKeyGenerator) {}

    /**
     * @param  array{organization: Organization|string, purchaser_user?: User|string|null, plan: LicensePlan|string, email: string, provider: string, provider_customer_id?: ?string, provider_checkout_id?: ?string, provider_subscription_id?: ?string, provider_metadata?: array<string, mixed>}  $data
     * @return array{license: License, license_key: ?string, created: bool}
     */
    public function execute(array $data): array
    {
        $plan = $data['plan'] instanceof LicensePlan
            ? $data['plan']
            : LicensePlan::from($data['plan']);
        $organizationId = $data['organization'] instanceof Organization
            ? $data['organization']->getKey()
            : (string) $data['organization'];
        $purchaserUserId = match (true) {
            ($data['purchaser_user'] ?? null) instanceof User => $data['purchaser_user']->getKey(),
            isset($data['purchaser_user']) && is_string($data['purchaser_user']) => $data['purchaser_user'],
            default => null,
        };

        return DB::transaction(function () use ($data, $organizationId, $plan, $purchaserUserId): array {
            $generatedKey = null;
            $providerCheckoutId = $data['provider_checkout_id'] ?? null;
            $createValues = function () use (&$generatedKey, $data, $organizationId, $plan, $purchaserUserId): array {
                $generatedKey = $this->licenseKeyGenerator->generate($plan);
                $defaults = $plan->defaults();

                return [
                    'organization_id' => $organizationId,
                    'purchaser_user_id' => $purchaserUserId,
                    'plan' => $plan,
                    'status' => LicenseStatus::Active,
                    'purchaser_email' => Str::of($data['email'])->trim()->lower()->toString(),
                    'license_key_hash' => $generatedKey['license_key_hash'],
                    'encrypted_license_key' => $generatedKey['license_key'],
                    'license_key_suffix' => $generatedKey['license_key_suffix'],
                    'seat_count' => $defaults['seat_count'],
                    'activation_limit' => $defaults['activation_limit'],
                    'updates_until' => now()->addYear(),
                    'expires_at' => null,
                    'provider_customer_id' => $data['provider_customer_id'] ?? null,
                    'provider_subscription_id' => $data['provider_subscription_id'] ?? null,
                    'provider_metadata' => $data['provider_metadata'] ?? [],
                ];
            };

            $license = $providerCheckoutId === null
                ? License::query()->create([
                    'provider' => $data['provider'],
                    'provider_checkout_id' => null,
                    ...$createValues(),
                ])
                : License::query()->firstOrCreate([
                    'provider' => $data['provider'],
                    'provider_checkout_id' => $providerCheckoutId,
                ], $createValues);

            if (! $license->wasRecentlyCreated) {
                return [
                    'license' => $license,
                    'license_key' => null,
                    'created' => false,
                ];
            }

            if ($generatedKey === null) {
                throw new LogicException('A newly created license must have a generated key.');
            }

            $license->events()->create([
                'type' => 'license.created',
                'metadata' => [
                    'plan' => $plan->value,
                    'organization_id' => $organizationId,
                    'provider' => $data['provider'],
                    'source' => $data['provider_metadata']['source'] ?? null,
                ],
            ]);

            return [
                'license' => $license,
                'license_key' => $generatedKey['license_key'],
                'created' => true,
            ];
        }, 3);
    }
}
