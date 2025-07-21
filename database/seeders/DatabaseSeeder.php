<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = \App\Account\Models\User::factory()->create();

        $team = \App\Team\Models\Team::factory()->create();
        $project = \App\Project\Models\Project::factory()->forTeam($team)->create();
        $environment = \App\Environment\Models\Environment::factory()->forProject($project)->create();

        app(\App\Secret\Actions\CreateSecret::class)->handle(
            owner: $project,
            name: 'Project Secret',
            type: \App\Secret\Enums\SecretType::GENERIC,
            value: 'project-secret',
            metadata: ['example' => true],
            createdBy: $user,
        );

        app(\App\Secret\Actions\CreateSecret::class)->handle(
            owner: $team,
            name: 'Team Secret',
            type: \App\Secret\Enums\SecretType::TOKEN,
            value: 'team-secret',
            metadata: null,
            createdBy: $user,
        );

        app(\App\Secret\Actions\CreateSecret::class)->handle(
            owner: $environment,
            name: 'Environment Secret',
            type: \App\Secret\Enums\SecretType::SSH_KEY,
            value: 'env-secret',
            metadata: null,
            createdBy: $user,
        );
    }
}
