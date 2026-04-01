<?php

namespace App\Account\Console\Commands;

use App\Backup\Support\EnvelopeEncryptor;
use App\Billing\Enums\Plan;
use App\Core\Concerns\CreatesAccountData;
use App\Crypto\Models\Device;
use App\Environment\Actions\CreateEnvironmentKey;
use App\Environment\Actions\StoreEnvironmentKeyEnvelope;
use App\Environment\Actions\StoreEnvironmentSecret;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Services\EnvironmentVariableCommentService;
use App\Environment\Services\EnvironmentVariableNoteService;
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

    private const GHOSTABLE_SETUP_PROJECT = 'Marketing Site';

    private const GHOSTABLE_RESHARE_LAB_PROJECT = 'Primary Server';

    /**
     * @var array<int, string>
     */
    private const GHOSTABLE_RESHARE_PREP_ENVIRONMENTS = ['production', 'staging', 'local'];

    private ?bool $shouldSeedVirtualKeyFixtures = null;

    private ?bool $shouldPrepareReshareLabAfterSetup = null;

    private ?string $reshareRunId = null;

    private bool $reshareSkipCliBuild = false;

    private string $reshareActorEmail = 'rucci.joe@gmail.com';

    private string $reshareRecipientEmail = 'nick@gmail.com';

    protected $signature = 'app:setup
        {--force}
        {--seed-virtual-key-fixtures : Seed synthetic devices/keys for demo browsing (not usable for local key operations).}
        {--prepare-reshare-lab : After setup, prepare pending local key re-share requests for the Primary Server production/staging/local environments.}
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

        $marketingSite = $this->createZeroKnowledgeProject(self::GHOSTABLE_SETUP_PROJECT, $ghostable);
        $productionEnvironment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $marketingSite);
        $stagingEnvironment = $this->createEnvironment('staging', EnvironmentType::STAGING, $marketingSite);
        $localEnvironment = $this->createEnvironment('local', EnvironmentType::LOCAL, $marketingSite);

        if ($this->seedVirtualKeyFixtures()) {
            $joeMac = $this->createDevice($joe, 'Joe MacBook Pro', 'macos');
            $nickPhone = $this->createDevice($nick, 'Nick iPhone', 'ios');

            $this->createEnvironmentKeyWithEnvelope(
                environment: $productionEnvironment,
                createdByDevice: $joeMac,
                recipients: [
                    ['id' => (string) $joeMac->id, 'type' => 'device', 'label' => 'Joe MacBook Pro'],
                    ['id' => (string) $nickPhone->id, 'type' => 'device', 'label' => 'Nick iPhone'],
                ],
            );
        }

        $seedDevice = $this->resolveGhostableSeedDevice($joe);

        [$productionKey, $productionKeyMaterial] = $this->createSeededEnvironmentKeyForDevice($productionEnvironment, $seedDevice);
        [$stagingKey, $stagingKeyMaterial] = $this->createSeededEnvironmentKeyForDevice($stagingEnvironment, $seedDevice);
        [$localKey, $localKeyMaterial] = $this->createSeededEnvironmentKeyForDevice($localEnvironment, $seedDevice);

        $this->createDeploymentToken(name: 'vercel-production', environment: $productionEnvironment, createdBy: $joe, expiresAfter: 90);
        $this->createDeploymentToken(name: 'github-actions-staging', environment: $stagingEnvironment, createdBy: $joe, expiresAfter: 60);

        $this->seedGhostableProductionEnvironmentData(
            environment: $productionEnvironment,
            environmentKey: $productionKey,
            keyMaterial: $productionKeyMaterial,
            joe: $joe,
            nick: $nick,
        );
        $this->seedGhostableStagingEnvironmentData(
            environment: $stagingEnvironment,
            environmentKey: $stagingKey,
            keyMaterial: $stagingKeyMaterial,
            joe: $joe,
            nick: $nick,
        );
        $this->seedGhostableLocalEnvironmentData(
            environment: $localEnvironment,
            environmentKey: $localKey,
            keyMaterial: $localKeyMaterial,
            joe: $joe,
            nick: $nick,
        );

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

    private function seedGhostableProductionEnvironmentData(
        Environment $environment,
        EnvironmentKey $environmentKey,
        string $keyMaterial,
        $joe,
        $nick
    ): void {
        $appKey = $this->seedEnvironmentSecretTimeline(
            environment: $environment,
            environmentKey: $environmentKey,
            keyMaterial: $keyMaterial,
            name: 'APP_KEY',
            versions: [
                ['value' => 'base64:x0zAwtwK2WdQ6B0fP2S6mD45bV9uQf3g7s4aY1hJ6L0=', 'actor' => $joe],
                ['value' => 'base64:e5dJq0x0d0P0t1R0aK+u8Nf2H9wLQ1cM4hW7vN2rA8U=', 'actor' => $joe, 'change_reason' => 'Rotated the application key after the quarterly platform security review.'],
            ],
            note: 'Primary application key for session and cookie encryption.',
            comments: [
                ['body' => 'Coordinate future rotations with release engineering so browser sessions and worker restarts are handled in the same window.', 'actor' => $joe],
                ['body' => 'If this changes during an incident, invalidate remembered sessions before reopening public access.', 'actor' => $nick],
            ],
        );

        $databasePassword = $this->seedEnvironmentSecretTimeline(
            environment: $environment,
            environmentKey: $environmentKey,
            keyMaterial: $keyMaterial,
            name: 'DB_PASSWORD',
            versions: [
                ['value' => 'gbl_prod_6rxN1Yw3kqM8', 'actor' => $joe],
                ['value' => 'gbl_prod_8vzR4Lp7naQ2', 'actor' => $joe, 'change_reason' => 'Rotated the primary database credential after the read/write split rollout.'],
                ['value' => 'gbl_prod_9qyT6Kd2mfP4', 'actor' => $nick, 'change_reason' => 'Rotated again after failover rehearsal to align the replica promotion runbook with production credentials.'],
            ],
            note: 'Password for the primary application database user.',
            comments: [
                ['body' => 'Keep this credential isolated to the application schema. Analytics and backup users remain separate.', 'actor' => $joe],
                ['body' => 'Validate migration, Horizon, and queue health immediately after the next credential rotation.', 'actor' => $nick],
            ],
        );

        $awsSecret = $this->seedEnvironmentSecretTimeline(
            environment: $environment,
            environmentKey: $environmentKey,
            keyMaterial: $keyMaterial,
            name: 'AWS_SECRET_ACCESS_KEY',
            versions: [
                ['value' => 's3cr3tghostableprodkey0001', 'actor' => $joe],
                ['value' => 's3cr3tghostableprodkey0002', 'actor' => $nick, 'change_reason' => 'Rotated the application asset key after narrowing S3 write access for deploy automation.'],
            ],
            note: 'Application AWS secret used for uploads, backups, and asset publishing.',
            comments: [
                ['body' => 'Keep the paired access key synchronized with the IAM policy revision in the deployment checklist.', 'actor' => $joe],
            ],
        );

        $this->attachEncryptedSeededComment($appKey, $keyMaterial, 'The last application-key rotation completed without session recovery issues.', $joe);
        $this->attachEncryptedSeededComment($databasePassword, $keyMaterial, 'Current value is the active credential used by the primary production cluster.', $joe);
        $this->attachEncryptedSeededComment($awsSecret, $keyMaterial, 'This key currently backs uploads, signed URLs, and release artifact storage.', $nick);

        foreach ([
            'APP_NAME' => 'Ghostable',
            'APP_ENV' => 'production',
            'APP_URL' => 'https://ghostable.com',
            'ASSET_URL' => 'https://assets.ghostable.com',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => 'db-primary.use1.ghostable.internal',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'ghostable',
            'DB_USERNAME' => 'ghostable_app',
            'CACHE_STORE' => 'redis',
            'QUEUE_CONNECTION' => 'redis',
            'SESSION_DRIVER' => 'redis',
            'SESSION_DOMAIN' => '.ghostable.com',
            'REDIS_HOST' => 'cache-primary.use1.ghostable.internal',
            'REDIS_PASSWORD' => 'cache_ghostable_prod_01',
            'REDIS_PORT' => '6379',
            'MAIL_MAILER' => 'ses',
            'MAIL_FROM_ADDRESS' => 'hello@ghostable.com',
            'MAIL_FROM_NAME' => 'Ghostable',
            'AWS_ACCESS_KEY_ID' => 'AKIAEXAMPLEGHOSTABLE01',
            'AWS_DEFAULT_REGION' => 'us-east-1',
            'SENTRY_LARAVEL_DSN' => 'https://9d6f2a4ce1bb4dd29e5b1234567890ef@o450123456.ingest.sentry.io/4507654322',
        ] as $name => $value) {
            $this->seedEnvironmentSecretTimeline(
                environment: $environment,
                environmentKey: $environmentKey,
                keyMaterial: $keyMaterial,
                name: $name,
                versions: [['value' => $value, 'actor' => $joe]],
            );
        }
    }

    private function seedGhostableStagingEnvironmentData(
        Environment $environment,
        EnvironmentKey $environmentKey,
        string $keyMaterial,
        $joe,
        $nick
    ): void {
        foreach ([
            'APP_NAME' => 'Ghostable',
            'APP_ENV' => 'staging',
            'APP_KEY' => 'base64:r2K3hN9cVwQ7xB1dU8kL4aJ0pS6mE3nY5tQ1wR7pL2U=',
            'APP_URL' => 'https://staging.ghostable.com',
            'ASSET_URL' => 'https://staging-assets.ghostable.com',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => 'db-staging.use1.ghostable.internal',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'ghostable_staging',
            'DB_USERNAME' => 'ghostable_stage',
            'DB_PASSWORD' => 'gbl_stage_3Mn7Zr6Q',
            'CACHE_STORE' => 'redis',
            'QUEUE_CONNECTION' => 'redis',
            'SESSION_DRIVER' => 'database',
            'REDIS_HOST' => 'cache-staging.use1.ghostable.internal',
            'REDIS_PASSWORD' => 'cache_stage_ghostable',
            'REDIS_PORT' => '6379',
            'MAIL_MAILER' => 'log',
            'MAIL_FROM_ADDRESS' => 'staging@ghostable.com',
            'AWS_ACCESS_KEY_ID' => 'AKIAEXAMPLEGHOSTABLESTAGE',
            'AWS_SECRET_ACCESS_KEY' => 'stageghostableawssecret01',
            'AWS_DEFAULT_REGION' => 'us-east-1',
        ] as $name => $value) {
            $versions = [['value' => $value, 'actor' => $joe]];

            if ($name === 'APP_URL') {
                $versions[] = [
                    'value' => 'https://app.staging.ghostable.com',
                    'actor' => $nick,
                    'change_reason' => 'Updated staging to mirror the production app subdomain layout before the release candidate cutover.',
                ];
            }

            $this->seedEnvironmentSecretTimeline(
                environment: $environment,
                environmentKey: $environmentKey,
                keyMaterial: $keyMaterial,
                name: $name,
                versions: $versions,
            );
        }
    }

    private function seedGhostableLocalEnvironmentData(
        Environment $environment,
        EnvironmentKey $environmentKey,
        string $keyMaterial,
        $joe,
        $nick
    ): void {
        foreach ([
            'APP_NAME' => 'Ghostable',
            'APP_ENV' => 'local',
            'APP_KEY' => 'base64:q8M4tL2xS0vN7dR5hC1pW9aK6uE3mF0zJ4nQ7rB2xY=',
            'APP_URL' => 'http://ghostable.test',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_DATABASE' => 'ghostable',
            'DB_USERNAME' => 'ghostable',
            'DB_PASSWORD' => 'password',
            'CACHE_STORE' => 'redis',
            'QUEUE_CONNECTION' => 'database',
            'SESSION_DRIVER' => 'database',
            'REDIS_HOST' => '127.0.0.1',
            'REDIS_PASSWORD' => 'null',
            'REDIS_PORT' => '6379',
            'MAIL_MAILER' => 'log',
            'MAIL_FROM_ADDRESS' => 'local@ghostable.test',
            'AWS_ACCESS_KEY_ID' => 'AKIAEXAMPLEGHOSTABLELOCAL',
            'AWS_SECRET_ACCESS_KEY' => 'ghostable-local-aws-secret-01',
            'AWS_DEFAULT_REGION' => 'us-east-1',
        ] as $name => $value) {
            $versions = [['value' => $value, 'actor' => $joe]];

            if ($name === 'QUEUE_CONNECTION') {
                $versions[] = [
                    'value' => 'redis',
                    'actor' => $nick,
                    'change_reason' => 'Switched the local queue backend to Redis so background job behavior matches staging more closely.',
                ];
            }

            $this->seedEnvironmentSecretTimeline(
                environment: $environment,
                environmentKey: $environmentKey,
                keyMaterial: $keyMaterial,
                name: $name,
                versions: $versions,
            );
        }
    }

    private function resolveGhostableSeedDevice($user): Device
    {
        $identity = $this->loadLocalDesktopIdentity();

        if ($identity === null) {
            return $this->createDevice($user, 'Ghostable Seed Desktop', 'macos', 'desktop');
        }

        /** @var Device|null $device */
        $device = Device::query()
            ->whereKey($identity['deviceId'])
            ->orWhere('public_key', $identity['encryptionPublicKey'])
            ->first();

        $device ??= new Device;

        $device->forceFill([
            'id' => $identity['deviceId'],
            'public_key' => $identity['encryptionPublicKey'],
            'public_signing_key' => $identity['signingPublicKey'],
            'name' => $identity['name'] ?: 'Ghostable Desktop',
            'platform' => $identity['platform'] ?: 'macos',
            'client_type' => $identity['clientType'] ?: 'desktop',
            'active' => true,
            'app_version' => 'local-seed',
            'last_seen_at' => now(),
        ]);
        $device->user()->associate($user);
        $device->save();

        return $device->fresh();
    }

    /**
     * @return array{deviceId: string, name: string, platform: string, clientType: string, signingPublicKey: string, encryptionPublicKey: string}|null
     */
    private function loadLocalDesktopIdentity(): ?array
    {
        if (PHP_OS_FAMILY !== 'Darwin' || app()->environment('testing')) {
            return null;
        }

        $service = sprintf(
            '%s.desktop.device.identity',
            trim((string) (getenv('GHOSTABLE_KEYCHAIN_PREFIX') ?: 'local.ghostable'))
        );

        $process = new Process([
            'security',
            'find-generic-password',
            '-s',
            $service,
            '-a',
            'identity',
            '-w',
        ]);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $decoded = json_decode(trim($process->getOutput()), true);

        if (! is_array($decoded)) {
            return null;
        }

        $deviceId = trim((string) ($decoded['deviceId'] ?? ''));
        $signingPublicKey = trim((string) ($decoded['signingPublicKey'] ?? ''));
        $encryptionPublicKey = trim((string) ($decoded['encryptionPublicKey'] ?? ''));

        if ($deviceId === '' || $signingPublicKey === '' || $encryptionPublicKey === '') {
            return null;
        }

        return [
            'deviceId' => $deviceId,
            'name' => trim((string) ($decoded['name'] ?? 'Ghostable Desktop')),
            'platform' => trim((string) ($decoded['platform'] ?? 'macos')),
            'clientType' => trim((string) ($decoded['clientType'] ?? 'desktop')),
            'signingPublicKey' => $signingPublicKey,
            'encryptionPublicKey' => $encryptionPublicKey,
        ];
    }

    /**
     * @return array{0: EnvironmentKey, 1: string}
     */
    private function createSeededEnvironmentKeyForDevice(Environment $environment, Device $device): array
    {
        $keyMaterial = random_bytes(32);
        $fingerprint = hash('sha256', $keyMaterial);

        $environmentKey = app(CreateEnvironmentKey::class)->handle(
            environment: $environment,
            fingerprint: $fingerprint,
            createdByDevice: $device,
            version: 1,
        );

        $dek = random_bytes(32);
        $nonce = random_bytes(24);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $keyMaterial,
            '',
            $nonce,
            $dek,
        );

        $recipientEnvelope = app(EnvelopeEncryptor::class)->encrypt(
            plaintext: $dek,
            recipientPublicKeyBase64: $device->public_key,
            meta: [
                'project_id' => (string) $environment->project_id,
                'environment' => $environment->name,
                'key_fingerprint' => $fingerprint,
            ],
        );

        app(StoreEnvironmentKeyEnvelope::class)->handle($environmentKey, [
            'ciphertext_b64' => base64_encode($ciphertext),
            'nonce_b64' => base64_encode($nonce),
            'alg' => 'xchacha20-poly1305',
            'version' => '1',
            'aad_b64' => null,
            'recipients' => [[
                'id' => (string) $device->getKey(),
                'type' => 'device',
                'label' => $device->name,
                'edek_b64' => 'b64:'.base64_encode(json_encode($recipientEnvelope, JSON_THROW_ON_ERROR)),
            ]],
        ]);

        return [$environmentKey->fresh(['envelope']), $keyMaterial];
    }

    /**
     * @param  array<int, array{
     *     value: string,
     *     actor?: mixed,
     *     change_reason?: string,
     *     is_commented?: bool,
     *     is_vapor_secret?: bool
     * }>  $versions
     * @param  array<int, array{body: string, actor?: mixed}>  $comments
     */
    private function seedEnvironmentSecretTimeline(
        Environment $environment,
        EnvironmentKey $environmentKey,
        string $keyMaterial,
        string $name,
        array $versions,
        ?string $note = null,
        array $comments = [],
    ): EnvironmentSecret {
        $secret = null;

        foreach ($versions as $version) {
            $changeReason = trim((string) ($version['change_reason'] ?? ''));

            $secret = $this->storeEncryptedSeededSecret(
                environment: $environment,
                environmentKey: $environmentKey,
                keyMaterial: $keyMaterial,
                name: $name,
                plaintext: $version['value'],
                actor: $version['actor'] ?? null,
                ifVersion: $secret?->version,
                isCommented: (bool) ($version['is_commented'] ?? false),
                isVaporSecret: (bool) ($version['is_vapor_secret'] ?? false),
                changeReason: $changeReason !== '' ? $changeReason : null,
            );
        }

        if (! $secret instanceof EnvironmentSecret) {
            throw new \RuntimeException(sprintf('Failed to seed timeline for secret [%s].', $name));
        }

        if ($note !== null && trim($note) !== '') {
            $this->attachEncryptedSeededNote(
                $secret,
                $keyMaterial,
                $note,
                $versions[array_key_last($versions)]['actor'] ?? null,
            );
        }

        foreach ($comments as $comment) {
            $this->attachEncryptedSeededComment(
                $secret,
                $keyMaterial,
                $comment['body'],
                $comment['actor'] ?? null,
            );
        }

        return $secret->fresh(['note', 'comments', 'versions.changeNote', 'latestVersion.changeNote']);
    }

    private function storeEncryptedSeededSecret(
        Environment $environment,
        EnvironmentKey $environmentKey,
        string $keyMaterial,
        string $name,
        string $plaintext,
        $actor = null,
        ?int $ifVersion = null,
        bool $isCommented = false,
        bool $isVaporSecret = false,
        ?string $changeReason = null,
    ): EnvironmentSecret {
        $payload = $this->buildEncryptedSecretPayload(
            environment: $environment,
            keyMaterial: $keyMaterial,
            name: $name,
            plaintext: $plaintext,
            isCommented: $isCommented,
            isVaporSecret: $isVaporSecret,
        );

        if ($ifVersion !== null) {
            $payload['if_version'] = $ifVersion;
        }

        if ($changeReason !== null) {
            $payload['change_note'] = $this->buildEncryptedContextPayload(
                environment: $environment,
                keyMaterial: $keyMaterial,
                variableName: $name,
                scope: 'change_note',
                plaintext: $changeReason,
            );
        }

        $secret = app(StoreEnvironmentSecret::class)->handle(
            $environment,
            $payload,
            $actor,
        );

        $secret->forceFill([
            'env_kek_version' => $environmentKey->version,
            'env_kek_fingerprint' => $environmentKey->fingerprint,
        ])->saveQuietly();

        $secret->versions()
            ->where('version', $secret->version)
            ->update([
                'env_kek_version' => $environmentKey->version,
                'env_kek_fingerprint' => $environmentKey->fingerprint,
            ]);

        return $secret->fresh(['versions.changeNote']);
    }

    private function attachEncryptedSeededNote(EnvironmentSecret $secret, string $keyMaterial, string $plaintext, $actor = null): void
    {
        app(EnvironmentVariableNoteService::class)->upsert(
            $secret,
            $this->buildEncryptedContextPayload(
                environment: $secret->environment,
                keyMaterial: $keyMaterial,
                variableName: $secret->name,
                scope: 'note',
                plaintext: $plaintext,
            ),
            $actor,
        );
    }

    private function attachEncryptedSeededComment(EnvironmentSecret $secret, string $keyMaterial, string $plaintext, $actor = null): void
    {
        app(EnvironmentVariableCommentService::class)->create(
            $secret,
            $this->buildEncryptedContextPayload(
                environment: $secret->environment,
                keyMaterial: $keyMaterial,
                variableName: $secret->name,
                scope: 'comment',
                plaintext: $plaintext,
            ),
            $actor,
        );
    }

    /**
     * @return array{name: string, ciphertext: string, nonce: string, alg: string, aad: array<string, string>, claims: array{hmac: string, meta: array<string, mixed>}, client_sig: string, line_bytes: int, is_vapor_secret: bool, is_commented: bool}
     */
    private function buildEncryptedSecretPayload(
        Environment $environment,
        string $keyMaterial,
        string $name,
        string $plaintext,
        bool $isCommented = false,
        bool $isVaporSecret = false,
    ): array {
        $org = Str::slug((string) $environment->project->organization->name);
        $project = Str::slug((string) $environment->project->name);
        $env = Str::slug((string) $environment->name);
        $aad = [
            'org' => $org,
            'project' => $project,
            'env' => $env,
            'name' => $name,
        ];
        $derived = $this->deriveSeedEncryptionKeys($keyMaterial, "{$org}/{$project}/{$env}");
        $nonce = random_bytes(24);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext,
            $this->encodeSecretAad($aad),
            $nonce,
            $derived['enc_key'],
        );

        return [
            'name' => $name,
            'ciphertext' => 'b64:'.base64_encode($ciphertext),
            'nonce' => 'b64:'.base64_encode($nonce),
            'alg' => 'xchacha20-poly1305',
            'aad' => $aad,
            'claims' => [
                'hmac' => 'b64:'.base64_encode(hash_hmac('sha256', $plaintext, $derived['hmac_key'], true)),
                'meta' => [
                    'value_length' => strlen($plaintext),
                    'is_vapor_secret' => $isVaporSecret,
                    'is_commented' => $isCommented,
                ],
            ],
            'client_sig' => base64_encode(random_bytes(64)),
            'line_bytes' => strlen($plaintext),
            'is_vapor_secret' => $isVaporSecret,
            'is_commented' => $isCommented,
        ];
    }

    /**
     * @return array{ciphertext: string, nonce: string, alg: string, aad: array<string, string>, claims: array{hmac: string}, client_sig: string}
     */
    private function buildEncryptedContextPayload(
        Environment $environment,
        string $keyMaterial,
        string $variableName,
        string $scope,
        string $plaintext,
    ): array {
        $org = Str::slug((string) $environment->project->organization->name);
        $project = Str::slug((string) $environment->project->name);
        $env = Str::slug((string) $environment->name);
        $aad = [
            'env' => $env,
            'org' => $org,
            'project' => $project,
            'scope' => $scope,
            'variable' => $variableName,
        ];
        $derived = $this->deriveSeedEncryptionKeys($keyMaterial, "{$org}/{$project}/{$env}/context/{$variableName}/{$scope}");
        $nonce = random_bytes(24);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext,
            $this->encodeSortedAad($aad),
            $nonce,
            $derived['enc_key'],
        );

        return [
            'ciphertext' => 'b64:'.base64_encode($ciphertext),
            'nonce' => 'b64:'.base64_encode($nonce),
            'alg' => 'xchacha20-poly1305',
            'aad' => $aad,
            'claims' => [
                'hmac' => 'b64:'.base64_encode(hash_hmac('sha256', $plaintext, $derived['hmac_key'], true)),
            ],
            'client_sig' => base64_encode(random_bytes(64)),
        ];
    }

    /**
     * @return array{enc_key: string, hmac_key: string}
     */
    private function deriveSeedEncryptionKeys(string $keyMaterial, string $context): array
    {
        $okm = hash_hkdf('sha256', $keyMaterial, 64, '', "ghostable:{$context}");

        return [
            'enc_key' => substr($okm, 0, 32),
            'hmac_key' => substr($okm, 32, 32),
        ];
    }

    /**
     * @param  array{org: string, project: string, env: string, name: string}  $aad
     */
    private function encodeSecretAad(array $aad): string
    {
        return sprintf(
            '{"org":"%s","project":"%s","env":"%s","name":"%s"}',
            addcslashes($aad['org'], "\\\"/\n\r\t"),
            addcslashes($aad['project'], "\\\"/\n\r\t"),
            addcslashes($aad['env'], "\\\"/\n\r\t"),
            addcslashes($aad['name'], "\\\"/\n\r\t"),
        );
    }

    /**
     * @param  array<string, string>  $aad
     */
    private function encodeSortedAad(array $aad): string
    {
        ksort($aad);

        return (string) json_encode($aad, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
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

        $organization = Organization::query()
            ->where('name', self::GHOSTABLE_SETUP_ORGANIZATION)
            ->first();

        if (! $organization instanceof Organization) {
            $this->warn(sprintf('Skipping re-share lab prep: organization [%s] was not found.', self::GHOSTABLE_SETUP_ORGANIZATION));

            return false;
        }

        if (! $organization->projects()->where('name', self::GHOSTABLE_RESHARE_LAB_PROJECT)->exists()) {
            $this->createZeroKnowledgeProject(self::GHOSTABLE_RESHARE_LAB_PROJECT, $organization);
        }

        $this->newLine();
        $this->info('🧪 Preparing local key re-share requests in the Primary Server project...');

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
            '--project-name', self::GHOSTABLE_RESHARE_LAB_PROJECT,
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
            $this->line('Tip: run `bash scripts/local-reshare-lab.sh --prepare-only --skip-app-setup --org-name Ghostable --project-name "Primary Server" --env-name production --env-name staging --env-name local` manually.');

            return false;
        }

        $this->info('✅ Re-share prep complete for production, staging, and local in Primary Server. Use `php artisan local:cli` to fulfill pending requests.');

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
                'Include the optional Primary Server re-share prep flow (production, staging, local)?',
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
