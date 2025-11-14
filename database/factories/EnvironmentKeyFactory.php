<?php

namespace Database\Factories;

use App\Crypto\Models\Device;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<EnvironmentKey>
 */
class EnvironmentKeyFactory extends Factory
{
    protected $model = EnvironmentKey::class;

    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'version' => 1,
            'fingerprint' => hash('sha256', Str::random(32)),
            'created_by_device_id' => null,
            'rotated_at' => null,
        ];
    }

    public function forEnvironment(Environment $environment): static
    {
        return $this->state(fn () => [
            'environment_id' => $environment->id,
        ]);
    }

    public function createdBy(Device $device): static
    {
        return $this->state(fn () => [
            'created_by_device_id' => $device->id,
        ]);
    }

    public function version(int $version): static
    {
        return $this->state(fn () => [
            'version' => $version,
        ]);
    }

    public function rotated(?Carbon $at = null): static
    {
        return $this->state(fn () => [
            'rotated_at' => ($at ?? now()),
        ]);
    }
}
