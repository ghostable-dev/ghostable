<?php

namespace Database\Factories;

use App\Account\Models\Team;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Project\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;
    
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'team_id' => Team::factory()
        ];
    }
    
    public function forTeam(Team $team): static
    {
        return $this->state([
            'team_id' => $team->id,
        ]);
    }
}
