<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Models\DesktopUpdateDailyRollup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DesktopUpdateDailyRollup>
 */
class DesktopUpdateDailyRollupFactory extends Factory
{
    protected $model = DesktopUpdateDailyRollup::class;

    public function definition(): array
    {
        $releaseVersion = fake()->numerify('1.#.#');

        return [
            'date' => fake()->dateTimeBetween('-30 days')->format('Y-m-d'),
            'event_type' => fake()->randomElement(DesktopUpdateEventType::cases()),
            'channel' => fake()->randomElement(['stable', 'beta']),
            'release_version' => $releaseVersion,
            'release_short_version' => $releaseVersion,
            'attributed' => fake()->boolean(),
            'total_events' => fake()->numberBetween(1, 50),
            'unique_ip_hashes' => fake()->numberBetween(1, 25),
            'unique_devices' => fake()->numberBetween(0, 15),
            'unique_users' => fake()->numberBetween(0, 15),
        ];
    }
}
