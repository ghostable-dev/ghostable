<?php

namespace Database\Factories;

use App\Account\Models\User;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentVariableNote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EnvironmentVariableNote>
 */
class EnvironmentVariableNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'environment_secret_id' => Str::uuid()->toString(),
            'ciphertext' => base64_encode($this->faker->sentence()),
            'nonce' => base64_encode(random_bytes(24)),
            'alg' => 'xchacha20-poly1305',
            'aad' => [
                'scope' => 'note',
            ],
            'claims' => [
                'meta' => [
                    'body_length' => $this->faker->numberBetween(12, 120),
                ],
            ],
            'client_sig' => base64_encode(random_bytes(64)),
            'created_by' => User::factory(),
            'last_updated_by' => User::factory(),
        ];
    }

    public function forSecret(EnvironmentSecret $secret): static
    {
        return $this->state(fn (): array => [
            'environment_secret_id' => $secret->getKey(),
        ]);
    }
}
