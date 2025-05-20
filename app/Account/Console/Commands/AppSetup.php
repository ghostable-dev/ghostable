<?php

namespace App\Account\Console\Commands;

use App\Account\Models\Team;
use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;
use App\Project\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AppSetup extends Command
{
    protected $signature = 'app:setup {--force}';
    protected $description = 'Run migrations, seeders, and setup default users for local development.';

    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  This will reset your database. Do you want to continue?')) {
                $this->warn('❌ Setup aborted. No changes were made to your database.');
                return;
            }
        }

        $this->resetDatabase();
        
        $this->seedCurricula();
    }
    
    protected function resetDatabase(): void
    {
        $this->info('🧹 Resetting database...');
        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed');
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
        $this->createEnvironment('production', $primary);
        $this->createEnvironment('staging', $primary);
        
        $phishing = $this->createProject('Phishing', $curricula);
        $phishingProduction = $this->createEnvironment('production', $phishing);
        $this->createEnvironment('staging', $phishing);
        $this->createEnvironment('local', $phishing);
        $this->createVariables($phishingProduction);
    }
    
    protected function createUser(string $name, string $email): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email
        ]); 
    }
    
    protected function createTeam(
        string $name, 
        User $owner, 
        array $members = []
    ): Team
    {
        $team =  Team::factory()->create([
            'name' => $name, 
            'owner_id' => $owner->id
        ]);
        
        $team->users()->attach($owner->id, ['role' => 'owner']);
        
        foreach ($members as $member) {
            $team->users()->attach($member->id);
        }
        
        return $team;
    }
    
    protected function createProject(string $name, Team $team): Project
    {
        return Project::factory()
            ->forTeam($team)
            ->create([
                'name' => $name,
            ]); 
    }
    
    protected function createEnvironment(string $name, Project $project): Environment
    {
        return Environment::factory()
            ->forProject($project)
            ->create([
                'name' => $name,
            ]); 
    }
    
    protected function createVariables(Environment $env, int $amount = 5): Collection
    {
        return EnvironmentVariable::factory()
            ->forEnvironment($env)
            ->count($amount)
            ->create();
    }
}
