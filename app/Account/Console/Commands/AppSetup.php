<?php

namespace App\Account\Console\Commands;

use App\Core\Concerns\CreatesAccountData;
use App\Environment\Enums\EnvironmentType;
use App\Blog\Seeders\PostSeeder;
use Illuminate\Console\Command;

class AppSetup extends Command
{
    use CreatesAccountData;

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

        $curricula = $this->createOrganization(
            name: 'Currciula',
            owner: $joe,
            members: [$tony]
        );

        $primary = $this->createProject('Primary', $curricula);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $primary);
        $this->createVariables(env: $production, amount: 10, createdBy: $joe);
        $staging = $this->createEnvironment('staging', EnvironmentType::STAGING, $primary, $production);
        $this->createVariables(env: $staging, amount: 4, createdBy: $joe);
        $local = $this->createEnvironment('local', EnvironmentType::LOCAL, $primary, $staging);
        $this->createVariables(env: $local, amount: 3, createdBy: $tony);

        $phishing = $this->createProject('Phishing', $curricula);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $phishing);
        $staging = $this->createEnvironment('staging', EnvironmentType::STAGING, $phishing, $production);
        $local = $this->createEnvironment('local', EnvironmentType::LOCAL, $phishing, $staging);
        $this->createEnvironment('local-jr', EnvironmentType::LOCAL, $phishing, $local);
        $this->createVariables(env: $production, amount: 10, createdBy: $joe);

        $this->createInvite(organization: $curricula, sender: $joe, email: 'nick@curricula.com');
    }

    protected function seedHuntress(): void
    {
        $joe = $this->createUser(name: 'Joe', email: 'joe@huntress.com');
        $jake = $this->createUser(name: 'Jake', email: 'jake@huntress.com');

        $huntress = $this->createOrganization(
            name: 'Huntress',
            owner: $joe,
            members: [$jake]
        );

        $primary = $this->createProject('Primary', $huntress);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $primary);
        $this->createVariables(env: $production, amount: 10, createdBy: $jake);
        $staging = $this->createEnvironment('staging', EnvironmentType::STAGING, $primary, $production);
        $this->createVariables(env: $staging, amount: 5, createdBy: $jake);

        $phishing = $this->createProject('Phishing', $huntress);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $phishing);
        $staging = $this->createEnvironment('staging', EnvironmentType::STAGING, $phishing, $production);
        $this->createEnvironment('local', EnvironmentType::LOCAL, $phishing, $staging);
        $this->createVariables(env: $production, amount: 10, createdBy: $joe);

        $this->createInvite(organization: $huntress, sender: $joe, email: 'joe@curricula.com');
    }
}
