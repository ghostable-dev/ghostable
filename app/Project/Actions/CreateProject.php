<?php

namespace App\Project\Actions;

use App\Environment\Actions\CreateEnv;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Organization\Models\Organization;
use App\Organization\Rules\WithinOrganizationProjectCap;
use App\Project\Entities\CreateProjectPayload;
use App\Project\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateProject
{
    protected Project $project;

    public function handle(CreateProjectPayload $payload): Project
    {
        $this->validate($payload->organization);

        DB::transaction(function () use ($payload) {
            $this->createProject($payload);

            if ($payload->withDefaultEnvironments) {
                $this->createDefaultEnvironments();
            }
        });

        return $this->project->refresh();
    }

    protected function validate(Organization $organization): void
    {
        Validator::make(
            ['project_limit' => null],
            ['project_limit' => [new WithinOrganizationProjectCap($organization)]],
        )->validate();
    }

    protected function createProject(CreateProjectPayload $payload): void
    {
        $this->project = new Project;

        $this->project->name = $payload->name;
        $this->project->description = $payload->description;

        $this->project->is_legacy = $payload->isLegacy;

        $this->project->organization()->associate($payload->organization);

        $this->project->deployment_provider = $payload->deploymentProvider;

        $this->project->stack = $payload->stack;

        $this->project->save();
    }

    protected function createDefaultEnvironments(): void
    {
        $this->createEnvironment(name: 'production', type: EnvironmentType::PRODUCTION);

        $this->createEnvironment(name: 'testing', type: EnvironmentType::TESTING);

        $this->createEnvironment(name: 'development', type: EnvironmentType::DEVELOPMENT);

        $this->createEnvironment(name: 'local', type: EnvironmentType::LOCAL);
    }

    protected function createEnvironment(
        string $name,
        EnvironmentType $type,
        ?Environment $base = null
    ): Environment {
        return resolve(CreateEnv::class)->handle(
            name: $name,
            type: $type,
            project: $this->project,
            base: $base
        );
    }
}
