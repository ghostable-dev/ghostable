<?php

namespace Database\Factories;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Environment\Models\EnvironmentVariable>
 */
class EnvironmentVariableFactory extends Factory
{
    protected $model = EnvironmentVariable::class;
    
    public function definition(): array
    {
        $key = strtoupper(fake()->unique()->randomElement([
            'APP_KEY',
            'APP_SECRET',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'STRIPE_KEY',
            'STRIPE_SECRET',
            'LOG_LEVEL',
            'QUEUE_CONNECTION',
        ]));

        $value = match ($key) {
            'APP_KEY', 'APP_SECRET', 'STRIPE_KEY', 'STRIPE_SECRET' => str()->random(32),
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
        ];
    }
    
    public function forEnvironment(Environment $environment): static
    {
        return $this->state([
            'environment_id' => $environment->id,
        ]);
    }
}
