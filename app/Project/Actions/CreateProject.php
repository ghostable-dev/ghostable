<?php

namespace App\Project\Actions;

use App\Environment\Actions\CreateEnv;
use App\Environment\Enums\EnvironmentType;
use App\Organization\Models\Organization;
use App\Organization\Rules\WithinOrganizationProjectCap;
use App\Project\Models\Project;
use Illuminate\Support\Facades\Validator;

class CreateProject
{
    public static function handle(string $name, Organization $organization, bool $populate = true): Project
    {
        Validator::make(
            ['project_limit' => null],
            ['project_limit' => [new WithinOrganizationProjectCap($organization)]],
        )->validate();

        $project = new Project;
        $project->name = $name;
        $project->organization()->associate($organization);
        $project->save();

        if ($populate) {
            $production = resolve(CreateEnv::class)->handle(
                name: 'production',
                type: EnvironmentType::PRODUCTION,
                project: $project
            );
            $testing = resolve(CreateEnv::class)->handle(
                name: 'testing',
                type: EnvironmentType::TESTING,
                project: $project,
                base: $production
            );
            $development = resolve(CreateEnv::class)->handle(
                name: 'development',
                type: EnvironmentType::DEVELOPMENT,
                project: $project,
                base: $testing
            );
            $local = resolve(CreateEnv::class)->handle(
                name: 'local',
                type: EnvironmentType::LOCAL,
                project: $project,
                base: $development
            );
        }

        return $project;
    }
}
