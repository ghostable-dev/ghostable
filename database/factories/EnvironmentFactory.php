<?php

namespace Database\Factories;

use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
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
        $type = fake()->randomElement(EnvironmentType::cases());

        return [
            'name' => $type->value,
            'type' => $type->value,
            'file_format' => EnvFileFormat::GROUPED->value,
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state([
            'project_id' => $project->id,
        ]);
    }

    public function basedOn(Environment $environment): static
    {
        return $this->state([
            'base_id' => $environment->id,
        ]);
    }
}
