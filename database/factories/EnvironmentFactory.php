<?php

namespace Database\Factories;

use App\Environment\Models\Environment;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Environment\Models\Environment>
 */
class EnvironmentFactory extends Factory
{
    protected $model = Environment::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Production',
                'local',
                'development',
                'testing',
                'staging']
            ),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state([
            'project_id' => $project->id,
        ]);
    }
}
