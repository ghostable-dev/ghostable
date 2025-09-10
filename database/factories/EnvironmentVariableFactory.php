<?php

namespace Database\Factories;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Variable\Models\EnvironmentVariable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EnvironmentVariableFactory extends Factory
{
    protected $model = EnvironmentVariable::class;

    public function definition(): array
    {
        $key = strtoupper(fake()->unique()->randomElement([
            'APP_KEY', 'APP_SECRET',
            'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD',
            'STRIPE_KEY', 'STRIPE_SECRET',
            'LOG_LEVEL', 'QUEUE_CONNECTION',
        ]));

        $value = match ($key) {
            'APP_KEY', 'APP_SECRET', 'STRIPE_KEY', 'STRIPE_SECRET' => Str::random(32),
            'DB_HOST', 'MAIL_HOST' => fake()->domainName(),
            'DB_PORT', 'MAIL_PORT' => fake()->numberBetween(1024, 65535),
            'DB_DATABASE' => fake()->slug(2),
            'DB_USERNAME', 'MAIL_USERNAME' => fake()->userName(),
            'DB_PASSWORD', 'MAIL_PASSWORD' => fake()->password(12),
            'LOG_LEVEL' => fake()->randomElement(['debug', 'info', 'warning', 'error']),
            'QUEUE_CONNECTION' => fake()->randomElement(['sync', 'database', 'redis']),
            default => fake()->word(),
        };

        return [
            'key' => $key,
            'value' => $value,
            'is_commented' => fake()->boolean(15), // ~15% chance commented
            'last_updated_by' => User::inRandomOrder()->first()?->id, // optional
            'last_updated_at' => now(),
        ];
    }

    public function forEnvironmentAndUser(Environment $environment, User $user): static
    {
        return $this->state([
            'environment_id' => $environment->id,
            'last_updated_by' => $user->id,
            'last_updated_at' => now(),
        ]);
    }

    public function forEnvironment(Environment $environment): static
    {
        $organizationUsers = $environment->project->organization->users;

        return $this->state(function () use ($environment, $organizationUsers) {
            return [
                'environment_id' => $environment->id,
                'last_updated_by' => $organizationUsers->isNotEmpty()
                    ? $organizationUsers->random()->id
                    : null,
                'last_updated_at' => now(),
            ];
        });
    }
}
