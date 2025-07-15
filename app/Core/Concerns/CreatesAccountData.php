<?php

namespace App\Core\Concerns;

use App\Account\Actions\RegisterUser;
use App\Account\Models\User;
use App\Environment\Actions\CreateEnv;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use App\Project\Models\Project;
use App\Team\Actions\CreateTeam;
use App\Team\Actions\CreateTeamInvite;
use App\Team\Enums\TeamRole;
use App\Team\Models\Team;
use Illuminate\Support\Collection;

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
        Project $project
    ): Environment {
        return app(CreateEnv::class)->handle(
            name: $name,
            type: $type,
            project: $project
        );
    }

    protected function createVariables(Environment $env, int $amount = 5): Collection
    {
        $vars = EnvironmentVariable::factory()
            ->forEnvironment($env)
            ->count($amount)
            ->create();

        foreach ($vars as $var) {
            $var->createVersionBy($var->lastUpdatedBy);
            $var->logActivity('created', user: $var->lastUpdatedBy);
        }

        return $vars;
    }
}
