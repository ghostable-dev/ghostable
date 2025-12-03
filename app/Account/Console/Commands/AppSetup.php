<?php

namespace App\Account\Console\Commands;

use App\Billing\Enums\Plan;
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

        // $this->seedHooli();

        // $this->seedAviato();

        // $this->seedLeeds();
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
        $nick = $this->createUser(name: 'Nick', email: 'nick@gmail.com');

        $ghostable = $this->createOrganization(
            name: 'Ghostable',
            owner: $joe,
            planOverride: Plan::ENTERPRISE,
        );

        $nick->organizationMembership()->assignToOrganization(organization: $ghostable, role: OrganizationRole::ADMIN);

        $primary = $this->createZeroKnowledgeProject('Primary', $ghostable);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $primary);

        $joeMac = $this->createDevice($joe, 'Joe MacBook Pro', 'macos');
        $nickPhone = $this->createDevice($nick, 'Nick iPhone', 'ios');

        $this->createEnvironmentKeyWithEnvelope(
            environment: $production,
            createdByDevice: $joeMac,
            recipients: [
                ['id' => (string) $joeMac->id, 'type' => 'device', 'label' => 'Joe MacBook Pro'],
                ['id' => (string) $nickPhone->id, 'type' => 'device', 'label' => 'Nick iPhone'],
            ],
        );

        $this->createDeploymentToken(name: 'gh-actions', environment: $production, createdBy: $joe, expiresAfter: 90);
        $this->createDeploymentToken(name: 'render-deploy', environment: $production, createdBy: $joe, expiresAfter: 60);

        $this->createZeroKnowledgeVariables(env: $production, amount: 5, createdBy: $joe);

        // $this->createVariables(env: $production, amount: 10, createdBy: $joe);
        // $this->createSecrets(env: $production, amount: 3, createdBy: $joe);

        // $staging = $this->createEnvironment('staging', EnvironmentType::STAGING, $primary, $production);
        // $this->createVariables(env: $staging, amount: 4, createdBy: $joe);
        // $this->createSecrets(env: $staging, amount: 2, createdBy: $joe);
        // $local = $this->createEnvironment('local', EnvironmentType::LOCAL, $primary, $staging);
        // $this->createVariables(env: $local, amount: 3, createdBy: $joe);
        // $this->createSecrets(env: $local, amount: 1, createdBy: $joe);

        // $this->createInvite(organization: $ghostable, sender: $joe, email: 'admin@ghostable.com');

        // $localCliToken = $this->createEnvToken(env: $local, createdBy: $joe);
        // $this->info('Token for local deploys: ' . $localCliToken->plainTextToken);
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
        $staging = $this->createEnvironment('staging', EnvironmentType::STAGING, $platform, $production);

        $compression = $this->createProject('Compression', $piedPiper);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $compression);
        $staging = $this->createEnvironment('staging', EnvironmentType::STAGING, $compression, $production);
        $local = $this->createEnvironment('local', EnvironmentType::LOCAL, $compression, $staging);

        $richardLaptop = $this->createDevice($richard, 'Richard MBP', 'macos');
        $gilfoyleRig = $this->createDevice($gilfoyle, 'Gilfoyle Tower', 'linux');

        $this->createEnvironmentKeyWithEnvelope(
            environment: $production,
            createdByDevice: $richardLaptop,
            recipients: [
                ['id' => (string) $richardLaptop->id, 'type' => 'device', 'label' => 'Richard MBP'],
                ['id' => (string) $gilfoyleRig->id, 'type' => 'device', 'label' => 'Gilfoyle Tower'],
            ],
        );

        $this->createDeploymentToken(name: 'jenkins-prod', environment: $production, createdBy: $richard, expiresAfter: 45);
        $this->createDeploymentToken(name: 'circleci-staging', environment: $staging, createdBy: $gilfoyle, expiresAfter: 45);

        $this->createZeroKnowledgeVariables(env: $production, amount: 4, createdBy: $richard);

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
    }

    protected function seedLeeds(): void
    {
        $bob = $this->createUser(name: 'Bob', email: 'bob@acme.com');
        // $bighead = $this->createUser(name: 'Big Head', email: 'bighead@aviato.com');

        // $aviato = $this->createOrganization(
        //     name: 'Aviato',
        //     owner: $erlich,
        // );

        // $bighead->organizationMembership()->assignToOrganization(organization: $aviato, role: OrganizationRole::DEVELOPER);

        // $app = $this->createProject('App', $aviato);
        // $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $app);
        // $this->createVariables(env: $production, amount: 5, createdBy: $erlich);
        // $this->createSecrets(env: $production, amount: 2, createdBy: $erlich);
    }
}
