<?php

namespace Database\Factories;

use App\Organization\Models\Organization;
use App\Project\Enums\DeploymentProvider;
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
            'deployment_provider' => DeploymentProvider::OTHER,
            'organization_id' => Organization::factory(),
        ];
    }

    public function forOrganization(Organization $organization): static
    {
        return $this->state([
            'organization_id' => $organization->id,
        ]);
    }
}
