<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Models\DesktopUpdateEvent;
use App\Crypto\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DesktopUpdateEvent>
 */
class DesktopUpdateEventFactory extends Factory
{
    protected $model = DesktopUpdateEvent::class;

    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-30 days');
        $releaseVersion = fake()->numerify('1.#.#');

        return [
            'event_type' => fake()->randomElement(DesktopUpdateEventType::cases()),
            'source' => fake()->randomElement(DesktopUpdateSource::cases()),
            'channel' => fake()->randomElement(['stable', 'beta']),
            'release_version' => $releaseVersion,
            'release_short_version' => $releaseVersion,
            'current_version' => fake()->numerify('1.#.#'),
            'from_version' => fake()->numerify('1.#.#'),
            'update_cycle_id' => (string) Str::uuid(),
            'device_id' => null,
            'user_id' => null,
            'attributed' => false,
            'ip_address' => '127.0.0.1',
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent' => 'GhostableDesktop/1.0',
            'metadata' => [],
            'rolled_up_at' => null,
            'ip_anonymized_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function attributed(Device $device): static
    {
        return $this->state(fn (): array => [
            'device_id' => $device->getKey(),
            'user_id' => $device->user_id,
            'attributed' => true,
        ]);
    }
}
