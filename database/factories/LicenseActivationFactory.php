<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Licensing\Models\License;
use App\Licensing\Models\LicenseActivation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenseActivation>
 */
class LicenseActivationFactory extends Factory
{
    protected $model = LicenseActivation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'user_id' => null,
            'device_id' => null,
            'activation_token_hash' => hash('sha256', fake()->uuid()),
            'machine_fingerprint_hash' => hash('sha256', fake()->uuid()),
            'machine_name' => fake()->word().' Mac',
            'platform' => 'macos',
            'app_version' => '0.1.0',
            'last_validated_at' => null,
            'deactivated_at' => null,
        ];
    }

    public function deactivated(): static
    {
        return $this->state(fn (): array => [
            'deactivated_at' => now(),
        ]);
    }
}
