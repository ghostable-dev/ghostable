<?php

declare(strict_types=1);

namespace App\Account\Actions;

use App\Account\Models\User;
use App\Api\Usage\Actions\UpsertApiUsageDaily;
use App\Auth\Models\PersonalAccessToken;
use App\Billing\Enums\Plan;
use App\Core\Concerns\CreatesAccountData;
use App\Crypto\Actions\LogDeviceActivity;
use App\Crypto\Actions\RegisterDevice;
use App\Crypto\Models\Device;
use App\Environment\Actions\CreateEnvironmentKey;
use App\Environment\Actions\LogEnvironmentDownloaded;
use App\Environment\Actions\LogEnvironmentViewed;
use App\Environment\Actions\RollbackEnvironmentSecret;
use App\Environment\Actions\StoreEnvironmentKeyEnvelope;
use App\Environment\Actions\StoreEnvironmentSecret;
use App\Environment\Actions\Token\CreateDeploymentToken;
use App\Environment\Actions\Token\LogEnvTokenActivity;
use App\Environment\Entities\RollbackResultData;
use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Environment\Models\EnvironmentSecret;
use App\Integration\Models\Integration;
use App\Integration\Models\IntegrationClient;
use App\Organization\Actions\CreateInvite as CreateOrganizationInvite;
use App\Organization\Actions\CreatePermissionOverride;
use App\Organization\Enums\OrganizationAuditWebhookStatus;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Invite;
use App\Organization\Models\Organization;
use App\Organization\Models\OrganizationAuditWebhook;
use App\Organization\Models\OrganizationAuditWebhookDelivery;
use App\Project\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

final class SeedScreenshotAccount
{
    use CreatesAccountData;

    private const ORGANIZATION_NAME = 'Northstar Labs';

    private const ORGANIZATION_SLUG = 'northstar-labs';

    private const EMAIL_DOMAIN = 'northstar.test';

    private const LOGIN_EMAIL = 'avery@northstar.test';

    private const LOGIN_PASSWORD = 'password';

    private CarbonImmutable $baseTime;

    private Organization $organization;

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, Project> */
    private array $projects = [];

    /** @var array<string, Environment> */
    private array $environments = [];

    /** @var array<string, Device> */
    private array $devices = [];

    /** @var array<string, EnvironmentKey> */
    private array $environmentKeys = [];

    /** @var array<string, Invite> */
    private array $invites = [];

    /** @var array<string, \App\Environment\Models\DeploymentToken> */
    private array $deploymentTokens = [];

    public function __construct(
        private readonly CreateDeploymentToken $createDeploymentToken,
        private readonly CreateEnvironmentKey $createEnvironmentKey,
        private readonly CreatePermissionOverride $createPermissionOverride,
        private readonly LogDeviceActivity $logDeviceActivity,
        private readonly LogEnvironmentDownloaded $logEnvironmentDownloaded,
        private readonly LogEnvironmentViewed $logEnvironmentViewed,
        private readonly LogEnvTokenActivity $logEnvTokenActivity,
        private readonly RegisterDevice $registerDevice,
        private readonly RollbackEnvironmentSecret $rollbackEnvironmentSecret,
        private readonly StoreEnvironmentKeyEnvelope $storeEnvironmentKeyEnvelope,
        private readonly StoreEnvironmentSecret $storeEnvironmentSecret,
        private readonly UpsertApiUsageDaily $upsertApiUsageDaily,
    ) {}

    public function handle(?Command $command = null): Organization
    {
        $this->baseTime = CarbonImmutable::instance(now()->utc()->startOfHour());

        $notificationRoot = Notification::getFacadeRoot();
        $queueRoot = Queue::getFacadeRoot();
        $originalNow = Carbon::getTestNow();

        Notification::fake();
        Queue::fake();

        try {
            DB::transaction(function () use ($command): void {
                $this->cleanupExistingFixtures();

                $this->line($command, 'Seeding Northstar Labs screenshot organization...');
                $this->createUsersAndOrganization();
                $this->createProjectsAndEnvironments();
                $this->createDevices();
                $this->seedIntegrations();
                $this->seedPermissionOverrides();
                $this->seedInvites();
                $this->seedEnvironmentKeys();
                $this->seedDeploymentTokens();
                $this->seedVariables();
                $this->seedActivity();
                $this->seedAuditWebhook();
                $this->seedApiUsage();
            });
        } finally {
            Carbon::setTestNow($originalNow);

            if ($notificationRoot !== null) {
                Notification::swap($notificationRoot);
            }

            if ($queueRoot !== null) {
                Queue::swap($queueRoot);
            }
        }

        return $this->organization->fresh([
            'users',
            'projects.environments',
            'invites',
        ]);
    }

    private function cleanupExistingFixtures(): void
    {
        $organizationIds = DB::table('organizations')
            ->where('slug', self::ORGANIZATION_SLUG)
            ->pluck('id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        $userIds = DB::table('users')
            ->where('email', 'like', '%@'.self::EMAIL_DOMAIN)
            ->pluck('id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        if ($organizationIds === [] && $userIds === []) {
            return;
        }

        $projectIds = DB::table('projects')
            ->whereIn('organization_id', $organizationIds ?: [''])
            ->pluck('id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        $environmentIds = DB::table('environments')
            ->whereIn('project_id', $projectIds ?: [''])
            ->pluck('id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        $deviceIds = DB::table('devices')
            ->whereIn('user_id', $userIds ?: [''])
            ->pluck('id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        $environmentKeyIds = DB::table('environment_keys')
            ->whereIn('environment_id', $environmentIds ?: [''])
            ->pluck('id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        $inviteIds = DB::table('organization_invites')
            ->where(function ($query) use ($organizationIds, $userIds): void {
                if ($organizationIds !== []) {
                    $query->whereIn('organization_id', $organizationIds);
                }

                if ($userIds !== []) {
                    $query->orWhereIn('user_id', $userIds);
                }
            })
            ->pluck('id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        $integrationClientIds = DB::table('integration_clients')
            ->whereIn('owner_organization_id', $organizationIds ?: [''])
            ->pluck('id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        $auditWebhookIds = DB::table('organization_audit_webhooks')
            ->whereIn('organization_id', $organizationIds ?: [''])
            ->pluck('id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        $tokenIds = DB::table('personal_access_tokens')
            ->where(function ($query) use ($userIds, $environmentIds): void {
                if ($userIds !== []) {
                    $query->where(function ($nested) use ($userIds): void {
                        $nested->where('tokenable_type', (new User)->getMorphClass())
                            ->whereIn('tokenable_id', $userIds);
                    });
                }

                if ($environmentIds !== []) {
                    $query->orWhere(function ($nested) use ($environmentIds): void {
                        $nested->where('tokenable_type', (new Environment)->getMorphClass())
                            ->whereIn('tokenable_id', $environmentIds);
                    });
                }
            })
            ->pluck('id')
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();

        DB::table('organization_audit_webhook_deliveries')
            ->where(function ($query) use ($organizationIds, $auditWebhookIds): void {
                if ($organizationIds !== []) {
                    $query->whereIn('organization_id', $organizationIds);
                }

                if ($auditWebhookIds !== []) {
                    $query->orWhereIn('organization_audit_webhook_id', $auditWebhookIds);
                }
            })
            ->delete();

        DB::table('environment_key_reshare_requests')
            ->where(function ($query) use ($organizationIds, $projectIds, $environmentIds, $userIds, $deviceIds): void {
                if ($organizationIds !== []) {
                    $query->whereIn('organization_id', $organizationIds);
                }

                if ($projectIds !== []) {
                    $query->orWhereIn('project_id', $projectIds);
                }

                if ($environmentIds !== []) {
                    $query->orWhereIn('environment_id', $environmentIds);
                }

                if ($userIds !== []) {
                    $query->orWhereIn('target_user_id', $userIds)
                        ->orWhereIn('resolved_by_user_id', $userIds);
                }

                if ($deviceIds !== []) {
                    $query->orWhereIn('target_device_id', $deviceIds);
                }
            })
            ->delete();

        DB::table('organization_permission_overrides')
            ->where(function ($query) use ($userIds, $projectIds, $environmentIds): void {
                if ($userIds !== []) {
                    $query->whereIn('user_id', $userIds);
                }

                if ($projectIds !== []) {
                    $query->orWhere(function ($nested) use ($projectIds): void {
                        $nested->where('target_type', Project::class)
                            ->whereIn('target_id', $projectIds);
                    });
                }

                if ($environmentIds !== []) {
                    $query->orWhere(function ($nested) use ($environmentIds): void {
                        $nested->where('target_type', Environment::class)
                            ->whereIn('target_id', $environmentIds);
                    });
                }
            })
            ->delete();

        DB::table('api_usage_daily')
            ->where(function ($query) use ($organizationIds, $tokenIds): void {
                if ($organizationIds !== []) {
                    $query->whereIn('organization_id', $organizationIds);
                }

                if ($tokenIds !== []) {
                    $query->orWhereIn('token_id', $tokenIds);
                }
            })
            ->delete();

        DB::table('api_usage_hourly')
            ->where(function ($query) use ($organizationIds, $tokenIds): void {
                if ($organizationIds !== []) {
                    $query->whereIn('organization_id', $organizationIds);
                }

                if ($tokenIds !== []) {
                    $query->orWhereIn('token_id', $tokenIds);
                }
            })
            ->delete();

        DB::table('envelopes')
            ->where('owner_type', (new EnvironmentKey)->getMorphClass())
            ->whereIn('owner_id', $environmentKeyIds ?: [''])
            ->delete();

        DB::table('messages')
            ->where('recipient_email', 'like', '%@'.self::EMAIL_DOMAIN)
            ->delete();

        $this->deleteActivityRows($organizationIds, $projectIds, $environmentIds, $tokenIds, $userIds, $inviteIds, $deviceIds);

        DB::table('organization_invites')
            ->where(function ($query) use ($organizationIds, $userIds): void {
                if ($organizationIds !== []) {
                    $query->whereIn('organization_id', $organizationIds);
                }

                if ($userIds !== []) {
                    $query->orWhereIn('user_id', $userIds)
                        ->orWhere('email', 'like', '%@'.self::EMAIL_DOMAIN);
                }
            })
            ->delete();

        if ($integrationClientIds !== []) {
            DB::table('integration_clients')->whereIn('id', $integrationClientIds)->delete();
        }

        if ($tokenIds !== []) {
            DB::table('personal_access_tokens')->whereIn('id', $tokenIds)->delete();
        }

        if ($organizationIds !== []) {
            DB::table('organizations')->whereIn('id', $organizationIds)->delete();
        }

        if ($userIds !== []) {
            DB::table('users')->whereIn('id', $userIds)->delete();
        }
    }

    /**
     * @param  string[]  $organizationIds
     * @param  string[]  $projectIds
     * @param  string[]  $environmentIds
     * @param  string[]  $tokenIds
     * @param  string[]  $userIds
     * @param  string[]  $inviteIds
     * @param  string[]  $deviceIds
     */
    private function deleteActivityRows(
        array $organizationIds,
        array $projectIds,
        array $environmentIds,
        array $tokenIds,
        array $userIds,
        array $inviteIds,
        array $deviceIds,
    ): void {
        $subjectMap = [
            (new Organization)->getMorphClass() => $organizationIds,
            (new Project)->getMorphClass() => $projectIds,
            (new Environment)->getMorphClass() => $environmentIds,
            (new PersonalAccessToken)->getMorphClass() => $tokenIds,
            (new User)->getMorphClass() => $userIds,
            (new Invite)->getMorphClass() => $inviteIds,
            (new Device)->getMorphClass() => $deviceIds,
        ];

        $causerMap = [
            (new User)->getMorphClass() => $userIds,
            (new PersonalAccessToken)->getMorphClass() => $tokenIds,
        ];

        DB::table('activity_log')
            ->where(function ($query) use ($subjectMap, $causerMap): void {
                foreach ($subjectMap as $type => $ids) {
                    if ($ids === []) {
                        continue;
                    }

                    $query->orWhere(function ($nested) use ($type, $ids): void {
                        $nested->where('subject_type', $type)
                            ->whereIn('subject_id', $ids);
                    });
                }

                foreach ($causerMap as $type => $ids) {
                    if ($ids === []) {
                        continue;
                    }

                    $query->orWhere(function ($nested) use ($type, $ids): void {
                        $nested->where('causer_type', $type)
                            ->whereIn('causer_id', $ids);
                    });
                }
            })
            ->delete();
    }

    private function createUsersAndOrganization(): void
    {
        $this->users['avery'] = $this->withFrozenTime(
            $this->time(daysAgo: 18),
            fn () => $this->createUser('Avery Stone', self::LOGIN_EMAIL)
        );
        $this->touchUser($this->users['avery'], $this->time(daysAgo: 0, hoursOffset: -2), 'America/New_York');

        $this->users['morgan'] = $this->withFrozenTime(
            $this->time(daysAgo: 17, hoursOffset: 2),
            fn () => $this->createUser('Morgan Lee', $this->email('morgan'))
        );
        $this->touchUser($this->users['morgan'], $this->time(daysAgo: 1, hoursOffset: -4));

        $this->users['riley'] = $this->withFrozenTime(
            $this->time(daysAgo: 17, hoursOffset: 3),
            fn () => $this->createUser('Riley Chen', $this->email('riley'))
        );
        $this->touchUser($this->users['riley'], $this->time(daysAgo: 0, hoursOffset: -5));

        $this->users['sam'] = $this->withFrozenTime(
            $this->time(daysAgo: 17, hoursOffset: 4),
            fn () => $this->createUser('Sam Patel', $this->email('sam'))
        );
        $this->touchUser($this->users['sam'], $this->time(daysAgo: 2, hoursOffset: -3));

        $this->users['jordan'] = $this->withFrozenTime(
            $this->time(daysAgo: 16, hoursOffset: 1),
            fn () => $this->createUser('Jordan Brooks', $this->email('jordan'))
        );
        $this->touchUser($this->users['jordan'], $this->time(daysAgo: 5, hoursOffset: -2));

        $this->users['casey'] = $this->withFrozenTime(
            $this->time(daysAgo: 16, hoursOffset: 2),
            fn () => $this->createUser('Casey Nguyen', $this->email('casey'))
        );
        $this->touchUser($this->users['casey'], $this->time(daysAgo: 6, hoursOffset: -1));

        $this->users['taylor'] = $this->withFrozenTime(
            $this->time(daysAgo: 16, hoursOffset: 3),
            fn () => $this->createUser('Taylor Cruz', $this->email('taylor'))
        );
        $this->touchUser($this->users['taylor'], $this->time(daysAgo: 1, hoursOffset: -2));

        $this->organization = $this->withFrozenTime(
            $this->time(daysAgo: 15),
            fn () => $this->createOrganization(
                name: self::ORGANIZATION_NAME,
                owner: $this->user('avery'),
                planOverride: Plan::ENTERPRISE,
            )
        );

        $this->withFrozenTime($this->time(daysAgo: 14, hoursOffset: 1), function (): void {
            $this->user('morgan')->organizationMembership()->assignToOrganization($this->organization, OrganizationRole::ADMIN);
            $this->user('riley')->organizationMembership()->assignToOrganization($this->organization, OrganizationRole::DEVELOPER);
            $this->user('sam')->organizationMembership()->assignToOrganization($this->organization, OrganizationRole::DEVELOPER);
            $this->user('jordan')->organizationMembership()->assignToOrganization($this->organization, OrganizationRole::DEVELOPER_READ_ONLY);
            $this->user('casey')->organizationMembership()->assignToOrganization($this->organization, OrganizationRole::BILLING_ONLY);
            $this->user('taylor')->organizationMembership()->assignToOrganization($this->organization, OrganizationRole::AUDITOR);
        });

        $this->updateModel($this->organization, [
            'slack_enabled' => true,
            'slack_webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/SCREENSHOTFIXTURE',
        ], $this->time(daysAgo: 15, hoursOffset: 1));
    }

    private function createProjectsAndEnvironments(): void
    {
        $this->projects['control_plane'] = $this->withFrozenTime(
            $this->time(daysAgo: 13),
            fn () => $this->createProject('Control Plane', $this->organization)
        );
        $this->updateModel($this->projects['control_plane'], [
            'description' => 'Internal operations console, worker queues, and release coordination.',
        ], $this->time(daysAgo: 13, hoursOffset: 1));

        $this->projects['customer_api'] = $this->withFrozenTime(
            $this->time(daysAgo: 13, hoursOffset: 1),
            fn () => $this->createProject('Customer API', $this->organization)
        );
        $this->updateModel($this->projects['customer_api'], [
            'description' => 'Public API cluster used by the customer dashboard and partner apps.',
            'is_restricted' => true,
        ], $this->time(daysAgo: 13, hoursOffset: 2));

        $this->projects['marketing_site'] = $this->withFrozenTime(
            $this->time(daysAgo: 13, hoursOffset: 2),
            fn () => $this->createProject('Marketing Site', $this->organization)
        );
        $this->updateModel($this->projects['marketing_site'], [
            'description' => 'Website, forms, and launch preview environments for campaigns.',
        ], $this->time(daysAgo: 13, hoursOffset: 3));

        $this->environments['control_plane_production'] = $this->withFrozenTime(
            $this->time(daysAgo: 12, hoursOffset: 1),
            fn () => $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project('control_plane'))
        );
        $this->environments['control_plane_staging'] = $this->withFrozenTime(
            $this->time(daysAgo: 12, hoursOffset: 2),
            fn () => $this->createEnvironment('staging', EnvironmentType::STAGING, $this->project('control_plane'))
        );
        $this->environments['control_plane_qa'] = $this->withFrozenTime(
            $this->time(daysAgo: 12, hoursOffset: 3),
            fn () => $this->createEnvironment('qa', EnvironmentType::QA, $this->project('control_plane'))
        );

        $this->environments['customer_api_production'] = $this->withFrozenTime(
            $this->time(daysAgo: 12, hoursOffset: 4),
            fn () => $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project('customer_api'))
        );
        $this->environments['customer_api_staging'] = $this->withFrozenTime(
            $this->time(daysAgo: 12, hoursOffset: 5),
            fn () => $this->createEnvironment('staging', EnvironmentType::STAGING, $this->project('customer_api'))
        );

        $this->environments['marketing_site_production'] = $this->withFrozenTime(
            $this->time(daysAgo: 12, hoursOffset: 6),
            fn () => $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project('marketing_site'))
        );
        $this->environments['marketing_site_preview'] = $this->withFrozenTime(
            $this->time(daysAgo: 12, hoursOffset: 7),
            fn () => $this->createEnvironment('preview', EnvironmentType::PREVIEW, $this->project('marketing_site'))
        );

        $this->updateModel($this->environment('customer_api_production'), ['is_restricted' => true], $this->time(daysAgo: 12, hoursOffset: 5));
        $this->updateModel($this->environment('control_plane_qa'), ['is_restricted' => true], $this->time(daysAgo: 12, hoursOffset: 4));
    }

    private function createDevices(): void
    {
        $this->devices['avery_desktop'] = $this->registerDevice(
            'avery_desktop',
            $this->user('avery'),
            'Avery Design Mac',
            'macos',
            'desktop',
            '1.5.0',
            $this->time(daysAgo: 0, hoursOffset: -2, minutesOffset: 15),
        );
        $this->devices['avery_cli'] = $this->registerDevice(
            'avery_cli',
            $this->user('avery'),
            'Avery CLI Runner',
            'macos',
            'cli',
            '2.4.1',
            $this->time(daysAgo: 0, hoursOffset: -5),
        );
        $this->devices['morgan_desktop'] = $this->registerDevice(
            'morgan_desktop',
            $this->user('morgan'),
            'Morgan Admin Mac',
            'macos',
            'desktop',
            '1.4.8',
            $this->time(daysAgo: 1, hoursOffset: -3),
        );
        $this->devices['riley_runner'] = $this->registerDevice(
            'riley_runner',
            $this->user('riley'),
            'Riley Linux Runner',
            'linux',
            'cli',
            '2.4.0',
            $this->time(daysAgo: 0, hoursOffset: -6),
        );
        $this->devices['sam_windows'] = $this->registerDevice(
            'sam_windows',
            $this->user('sam'),
            'Sam Windows Workstation',
            'windows',
            'cli',
            '2.3.9',
            $this->time(daysAgo: 2, hoursOffset: -4),
        );
        $this->devices['sam_revoked'] = $this->registerDevice(
            'sam_revoked',
            $this->user('sam'),
            'Sam Legacy Build Host',
            'linux',
            'cli',
            '2.2.7',
            $this->time(daysAgo: 19),
        );
        $this->devices['jordan_desktop'] = $this->registerDevice(
            'jordan_desktop',
            $this->user('jordan'),
            'Jordan Review Mac',
            'macos',
            'desktop',
            '1.4.6',
            $this->time(daysAgo: 5, hoursOffset: -2),
        );
        $this->devices['taylor_desktop'] = $this->registerDevice(
            'taylor_desktop',
            $this->user('taylor'),
            'Taylor Audit Mac',
            'macos',
            'desktop',
            '1.4.7',
            $this->time(daysAgo: 1, hoursOffset: -1),
        );

        $this->withFrozenTime($this->time(daysAgo: 7), function (): void {
            $device = $this->device('sam_revoked');
            $device->forceFill([
                'active' => false,
                'revoked_at' => $this->time(daysAgo: 7),
                'last_seen_at' => $this->time(daysAgo: 20),
            ])->saveQuietly();
        });

        $this->withFrozenTime($this->time(daysAgo: 11), function (): void {
            foreach (['avery_desktop', 'avery_cli', 'morgan_desktop', 'riley_runner', 'sam_windows', 'sam_revoked', 'jordan_desktop', 'taylor_desktop'] as $deviceKey) {
                $this->logDeviceActivity->handle(
                    device: $this->device($deviceKey),
                    event: 'created',
                    user: $this->device($deviceKey)->user,
                );
            }
        });

        $this->withFrozenTime($this->time(daysAgo: 7, hoursOffset: 1), function (): void {
            $this->logDeviceActivity->handle(
                device: $this->device('sam_revoked'),
                event: 'revoked',
                user: $this->user('morgan'),
            );
        });
    }

    private function seedIntegrations(): void
    {
        $client = $this->withFrozenTime($this->time(daysAgo: 9), function (): IntegrationClient {
            return IntegrationClient::query()->create([
                'name' => 'Northstar Compliance Gateway',
                'key' => 'northstar-compliance-gateway',
                'client_id' => 'northstar-compliance-gateway-client',
                'client_secret_hash' => Hash::make('northstar-compliance-gateway-secret'),
                'redirect_uris' => [
                    'https://northstar.example.com/oauth/callback',
                ],
                'default_scopes' => ['organization.read', 'project.read'],
                'status' => 'active',
                'owner_organization_id' => $this->organization->id,
                'publish_status' => IntegrationClient::PUBLISH_STATUS_PUBLISHED,
                'landing_page_url' => 'https://northstar.example.com/security',
                'description' => 'Synthetic compliance bridge used for screenshot-safe demo flows.',
            ]);
        });

        $this->withFrozenTime($this->time(daysAgo: 8, hoursOffset: 1), function () use ($client): void {
            Integration::factory()
                ->for($this->organization)
                ->drata()
                ->create([
                    'approved_by_user_id' => $this->user('avery')->id,
                    'approved_at' => $this->time(daysAgo: 8, hoursOffset: 1),
                    'connected_at' => $this->time(daysAgo: 8, hoursOffset: 1),
                ]);

            Integration::factory()
                ->for($this->organization)
                ->vanta()
                ->create([
                    'integration_client_id' => $client->id,
                    'approved_by_user_id' => $this->user('morgan')->id,
                    'approved_at' => $this->time(daysAgo: 8, hoursOffset: 2),
                    'connected_at' => $this->time(daysAgo: 8, hoursOffset: 2),
                ]);

            Integration::factory()
                ->for($this->organization)
                ->slack()
                ->create([
                    'approved_by_user_id' => $this->user('avery')->id,
                    'approved_at' => $this->time(daysAgo: 8, hoursOffset: 3),
                    'connected_at' => $this->time(daysAgo: 8, hoursOffset: 3),
                ]);
        });
    }

    private function seedPermissionOverrides(): void
    {
        $this->withFrozenTime($this->time(daysAgo: 6, hoursOffset: 2), function (): void {
            $this->createPermissionOverride->handle(
                user: $this->user('jordan'),
                target: $this->project('customer_api'),
                permission: OrganizationPermission::ManageProjectSettings,
                actor: $this->user('morgan'),
            );

            $this->createPermissionOverride->handle(
                user: $this->user('jordan'),
                target: $this->environment('control_plane_production'),
                permission: OrganizationPermission::EditVariables,
                actor: $this->user('morgan'),
            );

            $this->createPermissionOverride->handle(
                user: $this->user('taylor'),
                target: $this->environment('control_plane_production'),
                permission: OrganizationPermission::ViewSecrets,
                actor: $this->user('avery'),
            );
        });
    }

    private function seedInvites(): void
    {
        $this->invites['partner_ops'] = $this->withFrozenTime($this->time(daysAgo: 3, hoursOffset: 1), function (): Invite {
            return CreateOrganizationInvite::handle(
                organization: $this->organization,
                user: $this->user('avery'),
                email: $this->email('partner.ops'),
                role: OrganizationRole::DEVELOPER,
            );
        });

        $this->invites['compliance_reviewer'] = $this->withFrozenTime($this->time(daysAgo: 2, hoursOffset: 2), function (): Invite {
            return CreateOrganizationInvite::handle(
                organization: $this->organization,
                user: $this->user('morgan'),
                email: $this->email('compliance.reviewer'),
                role: OrganizationRole::AUDITOR,
            );
        });
    }

    private function seedEnvironmentKeys(): void
    {
        $this->environmentKeys['control_plane_production_v1'] = $this->createEnvironmentKeyFixture(
            environment: $this->environment('control_plane_production'),
            keyName: 'control_plane_production_v1',
            createdBy: $this->device('avery_desktop'),
            version: 1,
            rotatedAt: $this->time(daysAgo: 10),
            recipients: [
                $this->recipient($this->device('avery_desktop')),
                $this->recipient($this->device('avery_cli')),
                $this->recipient($this->device('riley_runner')),
            ],
        );

        $this->environmentKeys['control_plane_production_v2'] = $this->createEnvironmentKeyFixture(
            environment: $this->environment('control_plane_production'),
            keyName: 'control_plane_production_v2',
            createdBy: $this->device('morgan_desktop'),
            version: 2,
            rotatedAt: $this->time(daysAgo: 6),
            recipients: [
                $this->recipient($this->device('avery_desktop')),
                $this->recipient($this->device('avery_cli')),
                $this->recipient($this->device('morgan_desktop')),
                $this->recipient($this->device('riley_runner')),
                $this->recipient($this->device('taylor_desktop')),
            ],
        );

        $this->environmentKeys['control_plane_staging_v1'] = $this->createEnvironmentKeyFixture(
            environment: $this->environment('control_plane_staging'),
            keyName: 'control_plane_staging_v1',
            createdBy: $this->device('avery_cli'),
            version: 1,
            rotatedAt: $this->time(daysAgo: 9),
            recipients: [
                $this->recipient($this->device('avery_desktop')),
                $this->recipient($this->device('morgan_desktop')),
                $this->recipient($this->device('riley_runner')),
            ],
        );

        $this->environmentKeys['control_plane_qa_v1'] = $this->createEnvironmentKeyFixture(
            environment: $this->environment('control_plane_qa'),
            keyName: 'control_plane_qa_v1',
            createdBy: $this->device('avery_desktop'),
            version: 1,
            rotatedAt: $this->time(daysAgo: 8),
            recipients: [
                $this->recipient($this->device('avery_desktop')),
                $this->recipient($this->device('morgan_desktop')),
            ],
        );

        $this->environmentKeys['customer_api_production_v1'] = $this->createEnvironmentKeyFixture(
            environment: $this->environment('customer_api_production'),
            keyName: 'customer_api_production_v1',
            createdBy: $this->device('riley_runner'),
            version: 1,
            rotatedAt: $this->time(daysAgo: 8, hoursOffset: 1),
            recipients: [
                $this->recipient($this->device('avery_desktop')),
                $this->recipient($this->device('riley_runner')),
                $this->recipient($this->device('sam_windows')),
            ],
        );

        $this->environmentKeys['customer_api_staging_v1'] = $this->createEnvironmentKeyFixture(
            environment: $this->environment('customer_api_staging'),
            keyName: 'customer_api_staging_v1',
            createdBy: $this->device('riley_runner'),
            version: 1,
            rotatedAt: $this->time(daysAgo: 8, hoursOffset: 2),
            recipients: [
                $this->recipient($this->device('avery_desktop')),
                $this->recipient($this->device('riley_runner')),
            ],
        );

        $this->environmentKeys['marketing_site_production_v1'] = $this->createEnvironmentKeyFixture(
            environment: $this->environment('marketing_site_production'),
            keyName: 'marketing_site_production_v1',
            createdBy: $this->device('morgan_desktop'),
            version: 1,
            rotatedAt: $this->time(daysAgo: 7, hoursOffset: 3),
            recipients: [
                $this->recipient($this->device('avery_desktop')),
                $this->recipient($this->device('morgan_desktop')),
            ],
        );

        $this->environmentKeys['marketing_site_preview_v1'] = $this->createEnvironmentKeyFixture(
            environment: $this->environment('marketing_site_preview'),
            keyName: 'marketing_site_preview_v1',
            createdBy: $this->device('morgan_desktop'),
            version: 1,
            rotatedAt: $this->time(daysAgo: 7, hoursOffset: 4),
            recipients: [
                $this->recipient($this->device('avery_desktop')),
                $this->recipient($this->device('jordan_desktop')),
            ],
        );
    }

    private function seedDeploymentTokens(): void
    {
        $this->deploymentTokens['control_plane_actions'] = $this->issueDeploymentToken(
            key: 'control_plane_actions',
            name: 'github-actions-prod',
            environment: $this->environment('control_plane_production'),
            user: $this->user('avery'),
            createdAt: $this->time(daysAgo: 4, hoursOffset: 1),
            expiresAfter: 90,
        );

        $this->deploymentTokens['control_plane_render'] = $this->issueDeploymentToken(
            key: 'control_plane_render',
            name: 'render-bluegreen',
            environment: $this->environment('control_plane_production'),
            user: $this->user('morgan'),
            createdAt: $this->time(daysAgo: 3, hoursOffset: 2),
            expiresAfter: 60,
        );

        $this->deploymentTokens['customer_api_staging'] = $this->issueDeploymentToken(
            key: 'customer_api_staging',
            name: 'staging-smoke-check',
            environment: $this->environment('customer_api_staging'),
            user: $this->user('riley'),
            createdAt: $this->time(daysAgo: 2, hoursOffset: 1),
            expiresAfter: 30,
        );

        $this->withFrozenTime($this->time(daysAgo: 1, hoursOffset: 1), function (): void {
            $token = $this->deploymentToken('customer_api_staging');
            $personalAccessToken = $token->personalAccessToken()->first();

            if ($personalAccessToken) {
                $this->logEnvTokenActivity->handle($personalAccessToken, 'deleted', $this->user('morgan'));
            }

            $token->markRevoked();
        });
    }

    private function seedVariables(): void
    {
        $flagship = $this->environment('control_plane_production');
        $keyV1 = $this->environmentKey('control_plane_production_v1');
        $keyV2 = $this->environmentKey('control_plane_production_v2');

        $this->withFrozenTime($this->time(daysAgo: 9), function () use ($flagship, $keyV1): void {
            $this->storeSecret($flagship, $keyV1, $this->user('avery'), 'APP_URL', 'https://control.northstar.test', 'control-plane-app-url');
            $this->storeSecret($flagship, $keyV1, $this->user('avery'), 'REDIS_URL', 'redis://cache.internal:6379', 'control-plane-redis');
            $this->storeSecret($flagship, $keyV1, $this->user('avery'), 'VAPOR_API_TOKEN', 'vapor_demo_token_123456789', 'control-plane-vapor', vapor: true);
            $this->storeSecret($flagship, $keyV1, $this->user('avery'), 'MAINTENANCE_WINDOW', 'sundays-0100-0200-utc', 'control-plane-maintenance', commented: true);
            $this->storeSecret($flagship, $keyV1, $this->user('avery'), 'DATABASE_URL', 'mysql://control:v1@db.internal/control', 'control-plane-db-v1');
            $this->storeSecret($flagship, $keyV1, $this->user('riley'), 'FEATURE_PAYMENTS_ENABLED', 'true', 'control-plane-payments-v1');
        });

        $this->withFrozenTime($this->time(daysAgo: 6, hoursOffset: 2), function () use ($flagship, $keyV2): void {
            $databaseUrl = $flagship->envSecrets()->where('name', 'DATABASE_URL')->firstOrFail();

            $this->storeSecret(
                $flagship,
                $keyV2,
                $this->user('riley'),
                'DATABASE_URL',
                'mysql://control:v2@db.internal/control',
                'control-plane-db-v2',
                ifVersion: (int) $databaseUrl->version,
            );
        });

        $this->withFrozenTime($this->time(daysAgo: 4, hoursOffset: 3), function () use ($flagship, $keyV2): void {
            $databaseUrl = $flagship->envSecrets()->where('name', 'DATABASE_URL')->firstOrFail();

            $this->storeSecret(
                $flagship,
                $keyV2,
                $this->user('avery'),
                'DATABASE_URL',
                'mysql://control:v3@db.internal/control',
                'control-plane-db-v3',
                ifVersion: (int) $databaseUrl->version,
            );
        });

        $this->withFrozenTime($this->time(daysAgo: 3, hoursOffset: 2), function () use ($flagship, $keyV2): void {
            $featureFlag = $flagship->envSecrets()->where('name', 'FEATURE_PAYMENTS_ENABLED')->firstOrFail();

            $this->storeSecret(
                $flagship,
                $keyV2,
                $this->user('morgan'),
                'FEATURE_PAYMENTS_ENABLED',
                'false',
                'control-plane-payments-v2',
                ifVersion: (int) $featureFlag->version,
            );
        });

        $this->withFrozenTime($this->time(daysAgo: 2, hoursOffset: 2), function () use ($flagship): void {
            $featureFlag = $flagship->envSecrets()->where('name', 'FEATURE_PAYMENTS_ENABLED')->firstOrFail();
            $targetVersion = $featureFlag->versions()->where('version', 1)->firstOrFail();

            $result = $this->rollbackEnvironmentSecret->handle(
                secret: $featureFlag,
                targetVersion: $targetVersion,
                actor: $this->user('avery'),
                expectedVersion: (int) $featureFlag->version,
            );

            $this->logRollbackActivity($flagship, $this->device('avery_cli'), $result);
        });

        $this->withFrozenTime($this->time(daysAgo: 4), function (): void {
            $key = $this->environmentKey('customer_api_production_v1');
            $environment = $this->environment('customer_api_production');

            $this->storeSecret($environment, $key, $this->user('riley'), 'API_BASE_URL', 'https://api.northstar.test', 'customer-api-base-url');
            $this->storeSecret($environment, $key, $this->user('riley'), 'JWT_AUDIENCE', 'northstar-customer-api', 'customer-api-audience');
            $this->storeSecret($environment, $key, $this->user('sam'), 'QUEUE_CONNECTION', 'redis', 'customer-api-queue');
        });

        $this->withFrozenTime($this->time(daysAgo: 3, hoursOffset: 5), function (): void {
            $key = $this->environmentKey('marketing_site_preview_v1');
            $environment = $this->environment('marketing_site_preview');

            $this->storeSecret($environment, $key, $this->user('morgan'), 'NEXT_PUBLIC_SITE_URL', 'https://preview.northstar.test', 'marketing-preview-url');
            $this->storeSecret($environment, $key, $this->user('jordan'), 'ANALYTICS_WRITE_KEY', 'analytics-preview-key', 'marketing-preview-analytics');
        });
    }

    private function seedActivity(): void
    {
        $flagship = $this->environment('control_plane_production');

        $this->withFrozenTime($this->time(daysAgo: 10, hoursOffset: 1), function () use ($flagship): void {
            $this->logEnvironmentKeyActivity(
                event: 'environment_key_created',
                message: 'Created environment key v1 for "production".',
                environment: $flagship,
                key: $this->environmentKey('control_plane_production_v1'),
                user: $this->user('avery'),
                source: 'desktop',
            );
        });

        $this->withFrozenTime($this->time(daysAgo: 9, hoursOffset: 1), function () use ($flagship): void {
            activity('variable')
                ->performedOn($flagship)
                ->causedBy($this->user('avery'))
                ->event('push')
                ->withProperties([
                    'source' => 'cli',
                    'environment' => $this->environmentProperties($flagship),
                    'result' => [
                        'added' => 6,
                        'updated' => 0,
                        'removed' => 0,
                    ],
                    'request' => [
                        'sync' => false,
                        'force_overwrite' => false,
                        'secrets_submitted' => 6,
                    ],
                    'device' => $this->deviceProperties($this->device('avery_cli')),
                    'requested_by' => $this->userProperties($this->user('avery')),
                ])
                ->log('Pushed "production" environment via cli.');
        });

        $this->withFrozenTime($this->time(daysAgo: 8, hoursOffset: 4), function () use ($flagship): void {
            activity('variable')
                ->performedOn($flagship)
                ->causedBy($this->user('morgan'))
                ->event('commented')
                ->withProperties([
                    'source' => 'desktop',
                    'environment' => $this->environmentProperties($flagship),
                    'variable' => [
                        'name' => 'MAINTENANCE_WINDOW',
                        'version' => 1,
                    ],
                    'requested_by' => $this->userProperties($this->user('morgan')),
                ])
                ->log('Commented variable "MAINTENANCE_WINDOW" in "production" via desktop.');
        });

        $this->withFrozenTime($this->time(daysAgo: 6, hoursOffset: 3), function () use ($flagship): void {
            $this->logEnvironmentKeyActivity(
                event: 'environment_key_reshared',
                message: 'Re-shared environment key v2 for "production".',
                environment: $flagship,
                key: $this->environmentKey('control_plane_production_v2'),
                user: $this->user('morgan'),
                source: 'desktop',
            );
        });

        $this->withFrozenTime($this->time(daysAgo: 5, hoursOffset: 2), function () use ($flagship): void {
            $this->logEnvironmentViewed->handle($flagship, $this->user('avery'), 'desktop');
        });

        $this->withFrozenTime($this->time(daysAgo: 4, hoursOffset: 4), function () use ($flagship): void {
            $this->logEnvironmentDownloaded->handle($flagship, $this->user('riley'), 'cli');
        });

        $this->withFrozenTime($this->time(daysAgo: 2, hoursOffset: 4), function (): void {
            $pendingRequest = EnvironmentKeyReshareRequest::query()->create([
                'organization_id' => $this->organization->id,
                'project_id' => $this->project('control_plane')->id,
                'environment_id' => $this->environment('control_plane_qa')->id,
                'required_key_version' => $this->environmentKey('control_plane_qa_v1')->version,
                'target_user_id' => $this->user('jordan')->id,
                'target_device_id' => $this->device('jordan_desktop')->id,
                'status' => EnvironmentKeyReshareRequestStatus::Pending,
                'trigger_source' => 'device_link',
                'last_notified_at' => $this->time(daysAgo: 2, hoursOffset: 4),
            ]);

            activity('variable')
                ->performedOn($this->environment('control_plane_qa'))
                ->causedBy($this->user('morgan'))
                ->event('environment_key_reshare_requested')
                ->withProperties([
                    'source' => 'desktop',
                    'organization' => [
                        'id' => (string) $this->organization->id,
                        'name' => $this->organization->name,
                    ],
                    'project' => [
                        'id' => (string) $this->project('control_plane')->id,
                        'name' => $this->project('control_plane')->name,
                    ],
                    'environment' => $this->environmentProperties($this->environment('control_plane_qa')),
                    'environment_key' => [
                        'id' => (string) $this->environmentKey('control_plane_qa_v1')->id,
                        'version' => $this->environmentKey('control_plane_qa_v1')->version,
                        'fingerprint' => $this->environmentKey('control_plane_qa_v1')->fingerprint,
                    ],
                    'request' => [
                        'id' => (string) $pendingRequest->id,
                        'status' => $pendingRequest->status->value,
                        'trigger_source' => $pendingRequest->trigger_source,
                    ],
                    'target_device' => $this->deviceProperties($this->device('jordan_desktop')),
                    'target_user' => $this->userProperties($this->user('jordan')),
                    'requested_by' => $this->userProperties($this->user('morgan')),
                ])
                ->log('Requested key re-share for "qa" environment.');
        });

        $this->withFrozenTime($this->time(daysAgo: 1, hoursOffset: 2), function (): void {
            activity('organization')
                ->performedOn($this->organization)
                ->causedBy($this->user('avery'))
                ->event('settings_updated')
                ->withProperties([
                    'organization_id' => (string) $this->organization->id,
                    'areas' => ['alerts', 'audit_webhooks'],
                ])
                ->log('Updated organization alert routing for "Northstar Labs".');
        });
    }

    private function seedAuditWebhook(): void
    {
        $webhook = $this->withFrozenTime($this->time(daysAgo: 1, hoursOffset: 3), function (): OrganizationAuditWebhook {
            return OrganizationAuditWebhook::query()->create([
                'organization_id' => $this->organization->id,
                'name' => 'Northstar SIEM Relay',
                'endpoint_url' => 'https://siem.northstar.test/ghostable',
                'signing_secret' => 'northstar-screenshot-signing-secret',
                'status' => OrganizationAuditWebhookStatus::ACTIVE,
                'consecutive_failures' => 0,
                'last_delivered_at' => $this->time(daysAgo: 0, hoursOffset: -3),
                'created_by' => $this->user('avery')->id,
                'updated_by' => $this->user('morgan')->id,
            ]);
        });

        $this->withFrozenTime($this->time(daysAgo: 1, hoursOffset: 4), function () use ($webhook): void {
            OrganizationAuditWebhookDelivery::query()->create([
                'organization_audit_webhook_id' => $webhook->id,
                'organization_id' => $this->organization->id,
                'event_id' => 'evt-northstar-1',
                'event_type' => 'environment.push',
                'status' => 'delivered',
                'http_status' => 202,
                'latency_ms' => 88,
                'attempt_number' => 1,
                'delivered_at' => $this->time(daysAgo: 1, hoursOffset: 4),
                'created_at' => $this->time(daysAgo: 1, hoursOffset: 4),
                'updated_at' => $this->time(daysAgo: 1, hoursOffset: 4),
            ]);

            OrganizationAuditWebhookDelivery::query()->create([
                'organization_audit_webhook_id' => $webhook->id,
                'organization_id' => $this->organization->id,
                'event_id' => 'evt-northstar-2',
                'event_type' => 'environment.rollback',
                'status' => 'failed',
                'http_status' => 500,
                'latency_ms' => 214,
                'attempt_number' => 1,
                'error_message' => 'HTTP 500 from downstream collector',
                'created_at' => $this->time(daysAgo: 0, hoursOffset: -6),
                'updated_at' => $this->time(daysAgo: 0, hoursOffset: -6),
            ]);

            OrganizationAuditWebhookDelivery::query()->create([
                'organization_audit_webhook_id' => $webhook->id,
                'organization_id' => $this->organization->id,
                'event_id' => 'evt-northstar-3',
                'event_type' => 'organization.settings_updated',
                'status' => 'delivered',
                'http_status' => 200,
                'latency_ms' => 71,
                'attempt_number' => 2,
                'delivered_at' => $this->time(daysAgo: 0, hoursOffset: -3),
                'created_at' => $this->time(daysAgo: 0, hoursOffset: -3),
                'updated_at' => $this->time(daysAgo: 0, hoursOffset: -3),
            ]);
        });
    }

    private function seedApiUsage(): void
    {
        $organizationId = (string) $this->organization->id;

        foreach ([
            [$this->deploymentToken('control_plane_actions')->personalAccessToken?->id, 'POST', '/api/v2/projects/{project}/environments/production/push', $this->time(daysAgo: 2), 342],
            [$this->deploymentToken('control_plane_render')->personalAccessToken?->id, 'GET', '/api/v2/projects/{project}/environments/production/pull', $this->time(daysAgo: 1), 187],
            [$this->deploymentToken('customer_api_staging')->personalAccessToken?->id, 'POST', '/api/v2/projects/{project}/environments/staging/deploy', $this->time(daysAgo: 1), 29],
        ] as [$tokenId, $method, $endpoint, $date, $count]) {
            if (! is_string($tokenId) || $tokenId === '') {
                continue;
            }

            $this->withFrozenTime($date, function () use ($organizationId, $tokenId, $method, $endpoint, $date, $count): void {
                $this->upsertApiUsageDaily->handle(
                    organizationId: $organizationId,
                    tokenId: $tokenId,
                    method: $method,
                    endpoint: $endpoint,
                    day: Carbon::instance($date),
                    count: $count,
                );
            });
        }
    }

    private function registerDevice(
        string $seed,
        User $user,
        string $name,
        string $platform,
        string $clientType,
        string $appVersion,
        CarbonImmutable $lastSeenAt,
    ): Device {
        return $this->withFrozenTime($lastSeenAt, function () use ($seed, $user, $name, $platform, $clientType, $appVersion, $lastSeenAt): Device {
            $device = $this->registerDevice->handle(
                user: $user,
                publicKey: $this->seededBase64("device:{$seed}:public-key", 32),
                publicSigningKey: $this->seededBase64("device:{$seed}:public-signing-key", 32),
                name: $name,
                platform: $platform,
                clientType: $clientType,
            );

            $device->forceFill([
                'app_version' => $appVersion,
                'last_seen_at' => $lastSeenAt,
            ])->saveQuietly();

            return $device->fresh();
        });
    }

    private function createEnvironmentKeyFixture(
        Environment $environment,
        string $keyName,
        Device $createdBy,
        int $version,
        CarbonImmutable $rotatedAt,
        array $recipients,
    ): EnvironmentKey {
        return $this->withFrozenTime($rotatedAt, function () use ($environment, $keyName, $createdBy, $version, $rotatedAt, $recipients): EnvironmentKey {
            $environmentKey = $this->createEnvironmentKey->handle(
                environment: $environment,
                fingerprint: hash('sha256', "environment-key:{$keyName}"),
                createdByDevice: $createdBy,
                version: $version,
                rotatedAt: Carbon::instance($rotatedAt),
            );

            $this->storeEnvironmentKeyEnvelope->handle($environmentKey, [
                'ciphertext_b64' => $this->seededBase64("environment-key:{$keyName}:ciphertext", 64),
                'nonce_b64' => $this->seededBase64("environment-key:{$keyName}:nonce", 24),
                'alg' => 'xchacha20-poly1305',
                'version' => '1',
                'aad_b64' => base64_encode(json_encode([
                    'environment' => $environment->name,
                    'project' => $environment->project->name,
                    'organization' => $this->organization->name,
                ], JSON_THROW_ON_ERROR)),
                'recipients' => $recipients,
            ]);

            return $environmentKey->fresh(['envelope']);
        });
    }

    private function issueDeploymentToken(
        string $key,
        string $name,
        Environment $environment,
        User $user,
        CarbonImmutable $createdAt,
        int $expiresAfter,
    ): \App\Environment\Models\DeploymentToken {
        return $this->withFrozenTime($createdAt, function () use ($key, $name, $environment, $user, $expiresAfter): \App\Environment\Models\DeploymentToken {
            $result = $this->createDeploymentToken->handle(
                name: $name,
                environment: $environment,
                publicKey: $this->seededBase64("deployment-token:{$key}:public-key", SODIUM_CRYPTO_BOX_PUBLICKEYBYTES),
                user: $user,
                expiresAfter: $expiresAfter,
            );

            $token = $result->token->fresh(['personalAccessToken']);

            $personalAccessToken = $token->personalAccessToken()->first();
            if ($personalAccessToken) {
                $this->logEnvTokenActivity->handle($personalAccessToken, 'created', $user);
            }

            return $token;
        });
    }

    private function storeSecret(
        Environment $environment,
        EnvironmentKey $environmentKey,
        User $actor,
        string $name,
        string $plaintext,
        string $seed,
        bool $commented = false,
        bool $vapor = false,
        ?int $ifVersion = null,
    ): EnvironmentSecret {
        $payload = [
            'name' => $name,
            'ciphertext' => $this->seededBase64("secret:{$seed}:ciphertext", 48),
            'nonce' => $this->seededBase64("secret:{$seed}:nonce", 24),
            'alg' => 'xchacha20-poly1305',
            'aad' => [
                'organization' => (string) $this->organization->id,
                'project' => (string) $environment->project_id,
                'environment' => $environment->name,
                'name' => $name,
            ],
            'claims' => [
                'hmac' => hash_hmac('sha256', $plaintext, 'northstar-screenshot-seed'),
                'meta' => [
                    'value_length' => strlen($plaintext),
                    'is_vapor_secret' => $vapor,
                    'is_commented' => $commented,
                ],
            ],
            'client_sig' => $this->seededBase64("secret:{$seed}:signature", 64),
            'line_bytes' => strlen($plaintext),
            'is_vapor_secret' => $vapor,
            'is_commented' => $commented,
        ];

        if ($ifVersion !== null) {
            $payload['if_version'] = $ifVersion;
        }

        $secret = $this->storeEnvironmentSecret->handle($environment, $payload, $actor);

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

        return $secret->fresh(['versions']);
    }

    private function logRollbackActivity(
        Environment $environment,
        Device $device,
        RollbackResultData $result,
    ): void {
        activity('variable')
            ->performedOn($environment)
            ->causedBy($this->user('avery'))
            ->event('rollback')
            ->withProperties([
                'source' => 'cli',
                'environment' => $this->environmentProperties($environment),
                'variable' => [
                    'name' => $result->variableName(),
                    'rolled_back_to_version' => $result->rolledBackToVersion(),
                    'new_head_version' => $result->newVersion(),
                    'previous_head_version' => $result->previousHeadVersion,
                    'snapshot_id' => (string) $result->newSnapshot->getKey(),
                ],
                'device' => $this->deviceProperties($device),
                'requested_by' => $this->userProperties($this->user('avery')),
            ])
            ->log(sprintf(
                'Rolled back variable "%s" in "%s" to version %d (new head %d).',
                $result->variableName(),
                $environment->name,
                $result->rolledBackToVersion(),
                $result->newVersion(),
            ));
    }

    private function logEnvironmentKeyActivity(
        string $event,
        string $message,
        Environment $environment,
        EnvironmentKey $key,
        User $user,
        string $source,
    ): void {
        activity('variable')
            ->performedOn($environment)
            ->causedBy($user)
            ->event($event)
            ->withProperties([
                'source' => $source,
                'environment' => $this->environmentProperties($environment),
                'project' => [
                    'id' => (string) $environment->project->id,
                    'name' => $environment->project->name,
                ],
                'environment_key' => [
                    'id' => (string) $key->id,
                    'version' => (int) $key->version,
                    'fingerprint' => $key->fingerprint,
                ],
                'requested_by' => $this->userProperties($user),
            ])
            ->log($message);
    }

    private function touchUser(User $user, CarbonImmutable $lastLogin, string $timezone = 'UTC'): void
    {
        $this->updateModel($user, [
            'last_login' => $lastLogin,
            'timezone' => $timezone,
        ], $lastLogin);
    }

    private function updateModel(Model $model, array $attributes, CarbonImmutable $time): void
    {
        $this->withFrozenTime($time, function () use ($model, $attributes): void {
            $model->forceFill($attributes)->saveQuietly();
        });
    }

    private function email(string $localPart): string
    {
        return "{$localPart}@".self::EMAIL_DOMAIN;
    }

    private function withFrozenTime(CarbonImmutable $time, callable $callback): mixed
    {
        $originalNow = Carbon::getTestNow();

        Carbon::setTestNow($time);

        try {
            return $callback();
        } finally {
            Carbon::setTestNow($originalNow);
        }
    }

    private function time(int $daysAgo = 0, int $hoursOffset = 0, int $minutesOffset = 0): CarbonImmutable
    {
        return $this->baseTime
            ->subDays($daysAgo)
            ->addHours($hoursOffset)
            ->addMinutes($minutesOffset);
    }

    private function seededBase64(string $seed, int $length): string
    {
        return base64_encode($this->seededBytes($seed, $length));
    }

    private function seededBytes(string $seed, int $length): string
    {
        $buffer = '';
        $index = 0;

        while (strlen($buffer) < $length) {
            $buffer .= hash('sha256', "{$seed}:{$index}", true);
            $index++;
        }

        return substr($buffer, 0, $length);
    }

    private function line(?Command $command, string $message): void
    {
        if ($command) {
            $command->line($message);
        }
    }

    private function environmentProperties(Environment $environment): array
    {
        return [
            'id' => (string) $environment->id,
            'name' => $environment->name,
            'type' => $environment->type->value,
            'project_id' => (string) $environment->project_id,
            'project_name' => $environment->project->name,
            'organization_id' => (string) $this->organization->id,
            'organization_name' => $this->organization->name,
        ];
    }

    private function userProperties(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    private function deviceProperties(Device $device): array
    {
        return array_filter([
            'id' => (string) $device->id,
            'name' => $device->name,
            'platform' => $device->platform?->value,
            'app_version' => $device->app_version,
        ]);
    }

    private function recipient(Device $device): array
    {
        return [
            'id' => (string) $device->id,
            'type' => 'device',
            'label' => $device->name ?? 'Device',
        ];
    }

    private function user(string $key): User
    {
        return $this->users[$key];
    }

    private function project(string $key): Project
    {
        return $this->projects[$key];
    }

    private function environment(string $key): Environment
    {
        return $this->environments[$key];
    }

    private function device(string $key): Device
    {
        return $this->devices[$key];
    }

    private function environmentKey(string $key): EnvironmentKey
    {
        return $this->environmentKeys[$key];
    }

    private function deploymentToken(string $key): \App\Environment\Models\DeploymentToken
    {
        return $this->deploymentTokens[$key];
    }
}
