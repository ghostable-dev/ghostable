<?php

namespace App\Core\Concerns;

use App\Account\Actions\RegisterUser;
use App\Account\Models\User;
use App\Environment\Actions\CreateEnv;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Environment\Variable\Registry\VariableRegistry;
use App\Organization\Actions\CreateInvite;
use App\Organization\Actions\CreateOrganization;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use App\Secret\Actions\CreateSecret;
use App\Secret\Enums\SecretType;
use Illuminate\Support\Str;

trait CreatesAccountData
{
    protected function createUser(string $name, string $email): User
    {
        $user = app(RegisterUser::class)->handle([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
        ]);

        $user->markEmailAsVerified();
        $user->save();

        return $user->fresh();
    }

    protected function createOrganization(
        string $name,
        User $owner,
        array $members = []
    ): Organization {

        $organization = app(CreateOrganization::class)->handle(
            name: $name,
            owner: $owner
        );

        foreach ($members as $member) {
            $member->organizationMembership()->assignToOrganization(organization: $organization, role: OrganizationRole::DEVELOPER);
        }

        return $organization->fresh();
    }

    protected function createInvite(
        Organization $organization,
        User $sender,
        string $email,
        OrganizationRole $role = OrganizationRole::DEVELOPER
    ): void {
        CreateInvite::handle(
            organization: $organization,
            user: $sender,
            email: $email,
            role: $role
        );
    }

    protected function createProject(string $name, Organization $organization): Project
    {
        return Project::factory()
            ->forOrganization($organization)
            ->create([
                'name' => $name,
            ]);
    }

    protected function createEnvironment(
        string $name,
        EnvironmentType $type,
        Project $project,
        ?Environment $base = null
    ): Environment {
        return app(CreateEnv::class)->handle(
            name: $name,
            type: $type,
            project: $project,
            base: $base
        );
    }

    protected function createVariables(
        Environment $env,
        int $amount = 5,
        ?User $createdBy = null
    ) {
        for ($i = 0; $i < $amount; $i++) {
            $def = collect(
                resolve(VariableRegistry::class)->all()
            )->random();

            $data = new CreateVariableData(
                environment: $env,
                key: $def->key(),
                value: empty($def->suggestedValues())
                    ? 'some-random-value'
                    : collect($def->suggestedValues())->random(),
                createdBy: $createdBy
            );

            resolve(CreateVariable::class)->handle($data);
        }
    }

    protected function createSecrets(
        Environment $env,
        User $createdBy,
        int $amount = 5
    ): void {
        for ($i = 0; $i < $amount; $i++) {
            app(CreateSecret::class)->handle(
                environment: $env,
                name: 'secret_'.Str::random(8),
                type: collect(SecretType::cases())->random(),
                value: Str::random(32),
                metadata: null,
                createdBy: $createdBy,
            );
        }
    }
}
