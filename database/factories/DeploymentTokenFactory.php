<?php

namespace Database\Factories;

use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Project\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeploymentToken>
 */
class DeploymentTokenFactory extends Factory
{
    protected $model = DeploymentToken::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'public_key' => base64_encode(random_bytes(32)),
            'token_suffix' => Str::upper(Str::random(8)),
            'revoked_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (DeploymentToken $token): void {
            if ($token->environment_id && ! $token->project_id) {
                $environment = Environment::find($token->environment_id);

                if ($environment) {
                    $token->project_id = $environment->project_id;
                }
            }
        })->afterCreating(function (DeploymentToken $token): void {
            if ($token->environment_id && ! $token->project_id) {
                $environment = Environment::find($token->environment_id);

                if ($environment) {
                    $token->forceFill(['project_id' => $environment->project_id])->save();
                }
            }
        });
    }

    public function forEnvironment(?Environment $environment = null): static
    {
        return $this->state(function () use ($environment) {
            $environment ??= Environment::factory()->for(Project::factory())->create();

            return [
                'environment_id' => $environment->getKey(),
                'project_id' => $environment->project_id,
            ];
        });
    }
}
