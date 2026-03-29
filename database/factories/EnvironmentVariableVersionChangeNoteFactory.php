<?php

namespace Database\Factories;

use App\Account\Models\User;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Models\EnvironmentVariableVersionChangeNote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EnvironmentVariableVersionChangeNote>
 */
class EnvironmentVariableVersionChangeNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'environment_secret_version_id' => Str::uuid()->toString(),
            'ciphertext' => base64_encode($this->faker->sentence()),
            'nonce' => base64_encode(random_bytes(24)),
            'alg' => 'xchacha20-poly1305',
            'aad' => [
                'scope' => 'change_note',
            ],
            'claims' => [
                'meta' => [
                    'body_length' => $this->faker->numberBetween(12, 120),
                ],
            ],
            'client_sig' => base64_encode(random_bytes(64)),
            'created_by' => User::factory(),
        ];
    }

    public function forVersion(EnvironmentSecretVersion $version): static
    {
        return $this->state(fn (): array => [
            'environment_secret_version_id' => $version->getKey(),
        ]);
    }
}
