<?php

declare(strict_types=1);

namespace App\Licensing\Actions;

use App\Account\Models\User;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Models\License;
use App\Organization\Models\Organization;

class CreateFakeLicense
{
    public function __construct(private CreateLicense $createLicense) {}

    /**
     * @param  array{organization?: Organization|string, purchaser_user?: User|string|null, plan: LicensePlan|string, email: string, source?: string}  $data
     * @return array{license: License, license_key: string}
     */
    public function execute(array $data): array
    {
        $organization = $data['organization'] ?? Organization::query()->create([
            'name' => 'Desktop Licenses',
        ]);

        $result = $this->createLicense->execute([
            'organization' => $organization,
            'purchaser_user' => $data['purchaser_user'] ?? null,
            'plan' => $data['plan'],
            'email' => $data['email'],
            'provider' => 'fake',
            'provider_metadata' => [
                'source' => $data['source'] ?? 'dev_fake_purchase',
            ],
        ]);

        $license = $result['license'];

        $license->events()->create([
            'type' => 'license.fake_purchase_created',
            'metadata' => [
                'plan' => $license->plan->value,
                'organization_id' => $license->organization_id,
                'provider' => $license->provider,
                'source' => $license->provider_metadata['source'],
            ],
        ]);

        return [
            'license' => $license,
            'license_key' => (string) $result['license_key'],
        ];
    }
}
