<?php

namespace App\Account\Console\Commands;

use App\Billing\Enums\Plan;
use App\Core\Concerns\CreatesAccountData;
use App\Environment\Enums\EnvironmentType;
use App\Integration\Models\Integration;
use App\Integration\Models\IntegrationClient;
use App\Organization\Actions\PrepareLocalAuditWebhookCaptureStorage;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class AppSetup extends Command
{
    use CreatesAccountData;

    private const GHOSTABLE_SETUP_ORGANIZATION = 'Ghostable';

    private const GHOSTABLE_SETUP_PROJECT = 'Primary';

    /**
     * @var array<int, string>
     */
    private const GHOSTABLE_RESHARE_PREP_ENVIRONMENTS = ['primary', 'cli', 'desktop'];

    private ?bool $shouldSeedVirtualKeyFixtures = null;

    private ?bool $shouldPrepareReshareLabAfterSetup = null;

    private ?string $reshareRunId = null;

    private bool $reshareSkipCliBuild = false;

    private string $reshareActorEmail = 'rucci.joe@gmail.com';

    private string $reshareRecipientEmail = 'nick@gmail.com';

    protected $signature = 'app:setup
        {--force}
        {--seed-virtual-key-fixtures : Seed synthetic devices/keys for demo browsing (not usable for local key operations).}
        {--prepare-reshare-lab : After setup, prepare pending local key re-share requests for Ghostable primary/cli/desktop environments.}
        {--reshare-run-id= : Optional run identifier used when preparing the local re-share lab.}
        {--reshare-skip-cli-build : Skip CLI build when preparing the local re-share lab.}';

    protected $description = 'Run migrations, seeders, and setup default users for local development.';

    public function handle()
    {
        if (! $this->option('force')) {
            if (! $this->confirm('⚠️  Reset local database data now?', false)) {
                $this->warn('❌ Setup aborted. No changes were made to your database.');

                return;
            }
        }

        $this->resolveGuidedOptions();

        $this->resetDatabase();
        $this->ensureLocalAuditWebhookCaptureStorage();

        $this->seedGhostable();

        $this->seedPiedPiper();

        $this->seedHooli();

        // $this->seedAviato();

        // $this->seedLeeds();

        if ($this->shouldPrepareReshareLab()) {
            $prepared = $this->prepareLocalReshareLab();

            if ($prepared && $this->shouldLaunchLocalCliAfterSetup()) {
                $this->newLine();
                $this->info('🔄 Opening local:cli to fulfill pending key re-share requests...');
                $this->call('local:cli', ['--action' => 'fulfill-all']);
            }
        }
    }

    protected function resetDatabase(): void
    {
        $this->info('🧹 Resetting database...');
        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed');

        $this->info('Cleaing cache...');
        $this->call('cache:clear');
    }

    protected function ensureLocalAuditWebhookCaptureStorage(): void
    {
        app(PrepareLocalAuditWebhookCaptureStorage::class)->handle($this);
    }

    protected function seedGhostable(): void
    {
        $joe = $this->createUser(name: 'Joe Rucci', email: 'rucci.joe@gmail.com');
        $nick = $this->createUser(name: 'Nick', email: 'nick@gmail.com');

        $ghostable = $this->createOrganization(
            name: self::GHOSTABLE_SETUP_ORGANIZATION,
            owner: $joe,
            planOverride: Plan::ENTERPRISE,
        );
        $this->enableGuidedKeyReshare($ghostable);

        $nick->organizationMembership()->assignToOrganization(organization: $ghostable, role: OrganizationRole::ADMIN);

        $primaryProject = $this->createZeroKnowledgeProject(self::GHOSTABLE_SETUP_PROJECT, $ghostable);
        $primaryEnvironment = $this->createEnvironment('primary', EnvironmentType::PRODUCTION, $primaryProject);
        $cliEnvironment = $this->createEnvironment('cli', EnvironmentType::STAGING, $primaryProject);
        $desktopEnvironment = $this->createEnvironment('desktop', EnvironmentType::LOCAL, $primaryProject);

        if ($this->seedVirtualKeyFixtures()) {
            $joeMac = $this->createDevice($joe, 'Joe MacBook Pro', 'macos');
            $nickPhone = $this->createDevice($nick, 'Nick iPhone', 'ios');

            $this->createEnvironmentKeyWithEnvelope(
                environment: $primaryEnvironment,
                createdByDevice: $joeMac,
                recipients: [
                    ['id' => (string) $joeMac->id, 'type' => 'device', 'label' => 'Joe MacBook Pro'],
                    ['id' => (string) $nickPhone->id, 'type' => 'device', 'label' => 'Nick iPhone'],
                ],
            );
        }

        $this->createDeploymentToken(name: 'gh-actions', environment: $primaryEnvironment, createdBy: $joe, expiresAfter: 90);
        $this->createDeploymentToken(name: 'render-deploy', environment: $primaryEnvironment, createdBy: $joe, expiresAfter: 60);

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

        $this->seedGhostableIntegrations($ghostable);
    }

    protected function seedGhostableIntegrations(Organization $organization): void
    {
        Integration::factory()
            ->for($organization)
            ->drata()
            ->create();

        Integration::factory()
            ->for($organization)
            ->vanta()
            ->create();

        Integration::factory()
            ->for($organization)
            ->slack()
            ->create();
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
        $this->enableGuidedKeyReshare($piedPiper);

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

        if ($this->seedVirtualKeyFixtures()) {
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

            $this->createZeroKnowledgeVariables(env: $production, amount: 4, createdBy: $richard);
        }

        $this->createDeploymentToken(name: 'jenkins-prod', environment: $production, createdBy: $richard, expiresAfter: 45);
        $this->createDeploymentToken(name: 'circleci-staging', environment: $staging, createdBy: $gilfoyle, expiresAfter: 45);

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
        $this->enableGuidedKeyReshare($hooli);
        $hooli->forceFill(['is_partner' => true])->save();

        $bighead->organizationMembership()->assignToOrganization(organization: $hooli, role: OrganizationRole::DEVELOPER_READ_ONLY);
        $jianyang->organizationMembership()->assignToOrganization(organization: $hooli, role: OrganizationRole::DEVELOPER);

        $gavinDevice = $this->createDevice($gavin, 'Gavin MacBook Pro', 'macos');

        $box = $this->createProject('Hooli Box', $hooli);
        $production = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $box);

        $this->seedHooliIntegrationClient($hooli);
    }

    protected function seedVirtualKeyFixtures(): bool
    {
        if ($this->shouldSeedVirtualKeyFixtures === null) {
            $this->shouldSeedVirtualKeyFixtures = false;
        }

        return $this->shouldSeedVirtualKeyFixtures;
    }

    protected function seedHooliIntegrationClient(Organization $organization): void
    {
        $clientId = Str::random(32);
        $clientSecret = Str::random(64);
        $baseUrl = rtrim((string) config('app.url'), '/');
        $parts = parse_url($baseUrl);
        if (isset($parts['scheme']) && $parts['scheme'] !== 'https') {
            $baseUrl = preg_replace('/^http:\/\//', 'https://', $baseUrl) ?? $baseUrl;
        }
        $testUrl = $baseUrl.'/local/oauth-test';
        $redirectUri = $baseUrl.'/local/oauth-test/callback';

        IntegrationClient::query()->create([
            'name' => 'Hooli Internal Access',
            'key' => 'hooli-internal',
            'client_id' => $clientId,
            'client_secret_hash' => Hash::make($clientSecret),
            'redirect_uris' => [$redirectUri],
            'default_scopes' => ['organization.read'],
            'status' => 'active',
            'owner_organization_id' => $organization->id,
            'publish_status' => IntegrationClient::PUBLISH_STATUS_DRAFT,
        ]);

        $this->info('Hooli integration client created for local OAuth testing:');
        $this->info('Test URL: '.$testUrl);
        $this->info('Client ID: '.$clientId);
        $this->info('Client secret: '.$clientSecret);
        $this->info('Redirect URI: '.$redirectUri);
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

    private function enableGuidedKeyReshare(Organization $organization): void
    {
        $organization->features = $organization->features->withOverrides([
            'guided_key_reshare_v2' => true,
        ]);
        $organization->save();
    }

    private function shouldPrepareReshareLab(): bool
    {
        if ($this->shouldPrepareReshareLabAfterSetup === null) {
            return false;
        }

        return $this->shouldPrepareReshareLabAfterSetup;
    }

    private function prepareLocalReshareLab(): bool
    {
        $scriptPath = base_path('scripts/local-reshare-lab.sh');

        if (! is_file($scriptPath)) {
            $this->warn(sprintf('Skipping re-share lab prep: missing script at %s', $scriptPath));

            return false;
        }

        $this->newLine();
        $this->info('🧪 Preparing local key re-share requests for Ghostable environments (primary, cli, desktop)...');

        $sharedRunId = trim((string) ($this->reshareRunId ?? ''));
        if ($sharedRunId === '') {
            $sharedRunId = now()->format('YmdHis');
            $this->reshareRunId = $sharedRunId;
        }

        $args = [
            'bash',
            $scriptPath,
            '--skip-app-setup',
            '--prepare-only',
            '--org-name', self::GHOSTABLE_SETUP_ORGANIZATION,
            '--project-name', self::GHOSTABLE_SETUP_PROJECT,
            '--actor-email', $this->reshareActorEmail,
            '--recipient-email', $this->reshareRecipientEmail,
            '--secret-count', '5',
            '--run-id', $sharedRunId,
        ];

        foreach (self::GHOSTABLE_RESHARE_PREP_ENVIRONMENTS as $environmentName) {
            $args[] = '--env-name';
            $args[] = $environmentName;
        }

        if ($this->reshareSkipCliBuild) {
            $args[] = '--skip-cli-build';
        }

        $this->line(sprintf('• Environments: %s', implode(', ', self::GHOSTABLE_RESHARE_PREP_ENVIRONMENTS)));

        $process = new Process($args, base_path());
        $process->setTimeout(null);

        $exitCode = $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! is_int($exitCode) || $exitCode !== 0) {
            $this->warn('Re-share lab preparation failed for one or more environments.');
            $this->line('Tip: run `bash scripts/local-reshare-lab.sh --prepare-only --skip-app-setup --org-name Ghostable --project-name Primary --env-name primary --env-name cli --env-name desktop` manually.');

            return false;
        }

        $this->info('✅ Re-share prep complete for primary, cli, and desktop. Use `php artisan local:cli` to fulfill pending requests.');

        return true;
    }

    private function resolveGuidedOptions(): void
    {
        $this->shouldSeedVirtualKeyFixtures = (bool) $this->option('seed-virtual-key-fixtures');
        $this->shouldPrepareReshareLabAfterSetup = (bool) $this->option('prepare-reshare-lab');
        $this->reshareSkipCliBuild = (bool) $this->option('reshare-skip-cli-build');

        $runId = trim((string) ($this->option('reshare-run-id') ?? ''));
        $this->reshareRunId = $runId !== '' ? $runId : null;

        if ((bool) $this->option('no-interaction') || (bool) $this->option('force')) {
            return;
        }

        if (! $this->shouldPrepareReshareLabAfterSetup) {
            $this->shouldPrepareReshareLabAfterSetup = $this->confirm(
                'Include default local key re-share prep flow (primary, cli, desktop)?',
                true
            );
        }
    }

    private function shouldLaunchLocalCliAfterSetup(): bool
    {
        if ((bool) $this->option('no-interaction')) {
            return false;
        }

        if ((bool) $this->option('force')) {
            return false;
        }

        return true;
    }
}
