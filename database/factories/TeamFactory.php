<?php

namespace Database\Factories;

use App\Account\Models\Team;
use App\Account\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Account\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;
    
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            //'owner_id' => User::factory(),
        ];
    }
}
