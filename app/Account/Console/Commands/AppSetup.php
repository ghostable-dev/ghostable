<?php

namespace App\Account\Console\Commands;

use App\Core\Concerns\CreatesAccountData;
use App\Environment\Enums\EnvironmentType;
use App\Organization\Enums\OrganizationRole;
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

        $this->seedGhostable();

        $this->seedPiedPiper();

        $this->seedHooli();

        $this->seedAviato();
    }

    protected function resetDatabase(): void
    {
        $this->info('🧹 Resetting database...');
        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed');

        $this->info('Cleaing cache...');
        $this->call('cache:clear');
    }

    protected function seedGhostable(): void
    {
        $joe = $this->createUser(name: 'Joe Rucci', email: 'rucci.joe@gmail.com');

        $ghostable = $this->createOrganization(
            name: 'Ghostable',
            owner: $joe,
        );

        $primary = $this->createProject('Primary', $ghostable);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $primary);
        $this->createVariables(env: $production, amount: 10, createdBy: $joe);
        $this->createSecrets(env: $production, amount: 3, createdBy: $joe);
        $staging = $this->createEnvironment('staging', EnvironmentType::STAGING, $primary, $production);
        $this->createVariables(env: $staging, amount: 4, createdBy: $joe);
        $this->createSecrets(env: $staging, amount: 2, createdBy: $joe);
        $local = $this->createEnvironment('local', EnvironmentType::LOCAL, $primary, $staging);
        $this->createVariables(env: $local, amount: 3, createdBy: $joe);
        $this->createSecrets(env: $local, amount: 1, createdBy: $joe);

        $this->createInvite(organization: $ghostable, sender: $joe, email: 'admin@ghostable.com');
    }

    protected function seedPiedPiper(): void
    {
        $richard = $this->createUser(name: 'Richard Hendricks', email: 'richard@piedpiper.com');
        $gilfoyle = $this->createUser(name: 'Bertram Gilfoyle', email: 'gilfoyle@piedpiper.com');
        $dinesh = $this->createUser(name: 'Dinesh Chugtai', email: 'dinesh@piedpiper.com');
        $jared = $this->createUser(name: 'Jared Dunn', email: 'jared@piedpiper.com');
        $monica = $this->createUser(name: 'Monica Hall', email: 'monica@piedpiper.com');

        $piedPiper = $this->createOrganization(
            name: 'Pied Piper',
            owner: $richard,
        );

        $gilfoyle->organizationMembership()->assignToOrganization(organization: $piedPiper, role: OrganizationRole::DEVELOPER);
        $dinesh->organizationMembership()->assignToOrganization(organization: $piedPiper, role: OrganizationRole::DEVELOPER);
        $jared->organizationMembership()->assignToOrganization(organization: $piedPiper, role: OrganizationRole::ADMIN);
        $monica->organizationMembership()->assignToOrganization(organization: $piedPiper, role: OrganizationRole::BILLING_ONLY);

        $platform = $this->createProject('Platform', $piedPiper);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $platform);
        $this->createVariables(env: $production, amount: 10, createdBy: $gilfoyle);
        $this->createSecrets(env: $production, amount: 3, createdBy: $gilfoyle);
        $staging = $this->createEnvironment('staging', EnvironmentType::STAGING, $platform, $production);
        $this->createVariables(env: $staging, amount: 5, createdBy: $dinesh);
        $this->createSecrets(env: $staging, amount: 2, createdBy: $dinesh);

        $compression = $this->createProject('Compression', $piedPiper);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $compression);
        $staging = $this->createEnvironment('staging', EnvironmentType::STAGING, $compression, $production);
        $local = $this->createEnvironment('local', EnvironmentType::LOCAL, $compression, $staging);
        $this->createVariables(env: $production, amount: 10, createdBy: $richard);
        $this->createSecrets(env: $production, amount: 3, createdBy: $richard);
        $this->createVariables(env: $local, amount: 3, createdBy: $jared);
        $this->createSecrets(env: $local, amount: 1, createdBy: $jared);

        $this->createInvite(organization: $piedPiper, sender: $richard, email: 'gavin@hooli.com');
    }

    protected function seedHooli(): void
    {
        $gavin = $this->createUser(name: 'Gavin Belson', email: 'gavin@hooli.com');
        $bighead = $this->createUser(name: 'Nelson Bighetti', email: 'bighead@hooli.com');
        $jianyang = $this->createUser(name: 'Jian Yang', email: 'jianyang@hooli.com');

        $hooli = $this->createOrganization(
            name: 'Hooli',
            owner: $gavin,
        );

        $bighead->organizationMembership()->assignToOrganization(organization: $hooli, role: OrganizationRole::DEVELOPER_READ_ONLY);
        $jianyang->organizationMembership()->assignToOrganization(organization: $hooli, role: OrganizationRole::DEVELOPER);

        $box = $this->createProject('Hooli Box', $hooli);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $box);
        $this->createVariables(env: $production, amount: 5, createdBy: $gavin);
        $this->createSecrets(env: $production, amount: 2, createdBy: $gavin);
    }

    protected function seedAviato(): void
    {
        $erlich = $this->createUser(name: 'Erlich Bachman', email: 'erlich@aviato.com');
        $bighead = $this->createUser(name: 'Big Head', email: 'bighead@aviato.com');

        $aviato = $this->createOrganization(
            name: 'Aviato',
            owner: $erlich,
        );

        $bighead->organizationMembership()->assignToOrganization(organization: $aviato, role: OrganizationRole::DEVELOPER);

        $app = $this->createProject('App', $aviato);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $app);
        $this->createVariables(env: $production, amount: 5, createdBy: $erlich);
        $this->createSecrets(env: $production, amount: 2, createdBy: $erlich);
    }
}
