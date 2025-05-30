<?php

namespace App\Account\Console\Commands;

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
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AppSetup extends Command
{
    protected $signature = 'app:setup {--force}';

    protected $description = 'Run migrations, seeders, and setup default users for local development.';

    public function handle()
    {
        if (! $this->option('force')) {
            if (! $this->confirm('⚠️  This will reset your database. Do you want to continue?')) {
                $this->warn('❌ Setup aborted. No changes were made to your database.');

                return;
            }
        }

        $this->resetDatabase();

        $this->seedCurricula();

        $this->seedHuntress();
    }

    protected function resetDatabase(): void
    {
        $this->info('🧹 Resetting database...');
        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed');

        $this->info('Cleaing cache...');
        $this->call('cache:clear');
    }

    protected function seedCurricula(): void
    {
        $joe = $this->createUser(name: 'Joe Rucci', email: 'joe@curricula.com');
        $tony = $this->createUser(name: 'Tony Lea', email: 'tony@curricula.com');

        $curricula = $this->createTeam(
            name: 'Currciula',
            owner: $joe,
            members: [$tony]
        );

        $primary = $this->createProject('Primary', $curricula);
        $this->createEnvironment('Production', EnvironmentType::PRODUCTION, $primary);
        $this->createEnvironment('Staging', EnvironmentType::STAGING, $primary);

        $phishing = $this->createProject('Phishing', $curricula);
        $phishingProduction = $this->createEnvironment('Production', EnvironmentType::PRODUCTION, $phishing);
        $this->createEnvironment('Staging', EnvironmentType::STAGING, $phishing);
        $this->createEnvironment('Joe Local', EnvironmentType::LOCAL, $phishing);
        $this->createVariables($phishingProduction);

        $this->createInvite(team: $curricula, sender: $joe, email: 'nick@curricula.com');
    }

    protected function seedHuntress(): void
    {
        $joe = $this->createUser(name: 'Joe', email: 'joe@huntress.com');
        $jake = $this->createUser(name: 'Jake', email: 'jake@huntress.com');

        $huntress = $this->createTeam(
            name: 'Huntress',
            owner: $joe,
            members: [$jake]
        );

        $primary = $this->createProject('Primary', $huntress);
        $this->createEnvironment('production', EnvironmentType::PRODUCTION, $primary);
        $this->createEnvironment('staging', EnvironmentType::STAGING, $primary);

        $phishing = $this->createProject('Phishing', $huntress);
        $phishingProduction = $this->createEnvironment('Production', EnvironmentType::PRODUCTION, $phishing);
        $this->createEnvironment('Staging', EnvironmentType::STAGING, $phishing);
        $this->createEnvironment('JR Local', EnvironmentType::LOCAL, $phishing);
        $this->createVariables($phishingProduction);

        $this->createInvite(team: $huntress, sender: $joe, email: 'joe@curricula.com');
    }

    protected function createUser(string $name, string $email): User
    {
        $user = app(RegisterUser::class)->handle([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
        ]);

        $user->markEmailAsVerified();
        $user->save();

        return $user;
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
            $member->assignToTeam(team: $team, role: TeamRole::DEVELOPER);
        }

        return $team;
    }

    protected function createInvite(
        Team $team,
        User $sender,
        string $email,
        TeamRole $role = TeamRole::DEVELOPER
    ): void {
        CreateTeamInvite::handle(team: $team, user: $sender, email: $email, role: $role);
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
        return EnvironmentVariable::factory()
            ->forEnvironment($env)
            ->count($amount)
            ->create();
    }
}
