<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Licensing\Enums\LicensePlan;
use App\Licensing\Enums\LicenseStatus;
use App\Licensing\Models\License;
use App\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<License>
 */
class LicenseFactory extends Factory
{
    protected $model = License::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = LicensePlan::Personal;
        $defaults = $plan->defaults();
        $licenseKey = 'GHST-PERS-'.Str::upper(fake()->bothify('????-????-????-????'));

        return [
            'organization_id' => Organization::factory(),
            'purchaser_user_id' => null,
            'plan' => $plan,
            'status' => LicenseStatus::Active,
            'purchaser_email' => fake()->safeEmail(),
            'license_key_hash' => hash('sha256', fake()->uuid()),
            'encrypted_license_key' => $licenseKey,
            'license_key_suffix' => Str::of($licenseKey)->afterLast('-')->toString(),
            'seat_count' => $defaults['seat_count'],
            'activation_limit' => $defaults['activation_limit'],
            'updates_until' => now()->addYear(),
            'expires_at' => null,
            'provider' => 'factory',
            'provider_metadata' => [],
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => [
            'status' => LicenseStatus::Revoked,
        ]);
    }

    public function renewalRequired(): static
    {
        return $this->state(fn (): array => [
            'updates_until' => now()->subDay(),
        ]);
    }
}
