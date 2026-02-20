<?php

namespace Database\Factories;

use App\Account\Models\User;
use App\Crypto\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'platform' => $this->faker->randomElement([
                'unknown',
                'web',
                'ios',
                'android',
                'macos',
                'windows',
                'linux',
            ]),
            'app_version' => $this->faker->numerify('1.#.#'),
            'public_key' => base64_encode(random_bytes(32)),
            'public_signing_key' => base64_encode(random_bytes(32)),
            'active' => true,
            'last_seen_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'active' => false,
            'revoked_at' => now(),
        ]);
    }
}
