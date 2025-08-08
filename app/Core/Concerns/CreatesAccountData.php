<?php

namespace App\Core\Concerns;

use App\Account\Actions\RegisterUser;
use App\Account\Models\User;
use App\Environment\Actions\CreateEnv;
use App\Environment\Actions\CreateEnvVariable;
use App\Environment\Entities\CreateEnvVariableData;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use App\Environment\Registry\EnvironmentVariableRegistry;
use App\Project\Models\Project;
use App\Team\Actions\CreateTeam;
use App\Team\Actions\CreateTeamInvite;
use App\Team\Enums\TeamRole;
use App\Team\Models\Team;

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

    protected function createTeam(
        string $name,
        User $owner,
        array $members = []
    ): Team {

        $team = app(CreateTeam::class)->handle(
            name: $name,
            owner: $owner
        );

        foreach ($members as $member) {
            $member->teamMembership()->assignToTeam(team: $team, role: TeamRole::DEVELOPER);
        }

        return $team->fresh();
    }

    protected function createInvite(
        Team $team,
        User $sender,
        string $email,
        TeamRole $role = TeamRole::DEVELOPER
    ): void {
        CreateTeamInvite::handle(
            team: $team,
            user: $sender,
            email: $email,
            role: $role
        );
    }

    protected function createProject(string $name, Team $team): Project
    {
        return Project::factory()
            ->forTeam($team)
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
                resolve(EnvironmentVariableRegistry::class)->all()
            )->random();

            $data = new CreateEnvVariableData(
                environment: $env,
                key: $def->key(),
                value: empty($def->suggestedValues())
                    ? 'some-random-value'
                    : collect($def->suggestedValues())->random(),
                createdBy: $createdBy
            );

            resolve(CreateEnvVariable::class)->handle($data);
        }

        // $vars = EnvironmentVariable::factory()
        //     ->forEnvironment($env)
        //     ->count($amount)
        //     ->create();

        // dd($vars);

        // foreach ($vars as $var) {
        //     $var->createVersionBy($var->lastUpdatedBy);
        //     $var->logActivity('created', user: $var->lastUpdatedBy);
        // }

        // return $vars;
    }
}
