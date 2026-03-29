<?php

declare(strict_types=1);

use App\Account\Models\UserInboxNotification;
use App\Api\Usage\Support\UsageCacheKey;
use App\Api\Usage\Support\UsageDate;
use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentSecret;
use App\Organization\Enums\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->owner = $this->createUser('Dana', 'dana@example.com');
    $this->reader = $this->createUser('Ray', 'ray@example.com');
    $this->auditor = $this->createUser('Walter', 'walter@example.com');
    $this->billingOnly = $this->createUser('Louis', 'louis@example.com');

    $this->organization = $this->createOrganization('Ghostable', $this->owner);
    $this->reader->organizationMembership()->assignToOrganization(
        organization: $this->organization,
        role: OrganizationRole::DEVELOPER_READ_ONLY,
    );
    $this->auditor->organizationMembership()->assignToOrganization(
        organization: $this->organization,
        role: OrganizationRole::AUDITOR,
    );
    $this->billingOnly->organizationMembership()->assignToOrganization(
        organization: $this->organization,
        role: OrganizationRole::BILLING_ONLY,
    );

    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment(
        'production',
        EnvironmentType::PRODUCTION,
        $this->project
    );

    $this->secret = EnvironmentSecret::query()->create([
        'environment_id' => $this->environment->id,
        'name' => 'APP_KEY',
        'ciphertext' => base64_encode('ciphertext-current'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $this->environment->id, 'name' => 'APP_KEY'],
        'claims' => ['hmac' => 'current-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fp-current',
        'metadata' => [],
        'line_bytes' => 16,
        'is_commented' => false,
        'version' => 1,
        'last_updated_by' => $this->owner->id,
        'last_updated_at' => now(),
    ]);

    $deviceKeypair = sodium_crypto_sign_keypair();
    $this->deviceSecretKey = sodium_crypto_sign_secretkey($deviceKeypair);
    $devicePublicKey = sodium_crypto_sign_publickey($deviceKeypair);

    $this->device = Device::factory()->for($this->owner)->create([
        'active' => true,
        'revoked_at' => null,
        'client_type' => 'desktop',
        'public_signing_key' => base64_encode($devicePublicKey),
    ]);

    $this->signPayload = function (array $payload): array {
        $payloadToSign = $payload;
        unset($payloadToSign['client_sig']);

        $payloadJson = json_encode(
            $payloadToSign,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $payload['client_sig'] = base64_encode(
            sodium_crypto_sign_detached($payloadJson, $this->deviceSecretKey)
        );

        return $payload;
    };

    $this->makeEncryptedBody = function (string $plaintext): array {
        $payload = [
            'ciphertext' => base64_encode($plaintext),
            'nonce' => base64_encode(random_bytes(24)),
            'alg' => 'xchacha20-poly1305',
            'aad' => [
                'env' => (string) $this->environment->id,
                'variable' => $this->secret->name,
                'scope' => 'comment',
            ],
            'claims' => [
                'hmac' => hash('sha256', $plaintext),
                'meta' => [
                    'body_length' => strlen($plaintext),
                ],
            ],
            'client_sig' => 'pending',
        ];

        return ($this->signPayload)($payload);
    };

    $this->commentEndpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/variables/%s/context/comments',
        $this->project->id,
        $this->environment->name,
        $this->secret->name,
    );

    $this->inboxEndpoint = sprintf(
        '/api/v2/organizations/%s/notifications/inbox',
        $this->organization->id,
    );
    $this->inboxUnreadCountEndpoint = $this->inboxEndpoint.'/unread-count';
});

test('comment alerts fan out as metadata only to authorized teammates and support read state', function (): void {
    Sanctum::actingAs($this->owner);

    $commentPlaintext = 'Coordinate with support before rotating the key.';
    $commentPayload = ($this->makeEncryptedBody)($commentPlaintext);

    $commentId = $this->postJson($this->commentEndpoint, [
        'device_id' => (string) $this->device->getKey(),
        'comment' => $commentPayload,
    ])
        ->assertCreated()
        ->json('data.comment_id');

    expect($commentId)->toBeString()
        ->and(UserInboxNotification::query()->count())->toBe(1);

    $notification = UserInboxNotification::query()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->user_id)->toBe($this->reader->id)
        ->and($notification->actor_id)->toBe($this->owner->id)
        ->and($notification->reference_id)->toBe($commentId)
        ->and($notification->description)->toContain('Dana commented on "APP_KEY" in "production".');

    $notificationJson = json_encode($notification->toArray(), JSON_THROW_ON_ERROR);

    expect($notificationJson)->not->toContain($commentPlaintext)
        ->and($notificationJson)->not->toContain($commentPayload['ciphertext']);

    Sanctum::actingAs($this->reader);

    $listResponse = $this->getJson($this->inboxEndpoint)
        ->assertOk()
        ->assertJsonPath('data.meta.unread_count', 1)
        ->assertJsonPath('data.entries.0.event', UserInboxNotification::EVENT_CONTEXT_COMMENT_ADDED)
        ->assertJsonPath('data.entries.0.variable.name', 'APP_KEY')
        ->assertJsonPath('data.entries.0.environment.name', 'production')
        ->assertJsonPath('data.entries.0.actor.name', 'Dana');

    expect($listResponse->getContent())->not->toContain($commentPlaintext)
        ->and($listResponse->getContent())->not->toContain($commentPayload['ciphertext']);

    $this->getJson($this->inboxUnreadCountEndpoint)
        ->assertOk()
        ->assertJsonPath('data.unread_count', 1);

    $this->postJson($this->inboxEndpoint.'/'.$notification->id.'/read')
        ->assertOk()
        ->assertJsonPath('status', 'updated');

    $this->getJson($this->inboxUnreadCountEndpoint)
        ->assertOk()
        ->assertJsonPath('data.unread_count', 0);

    $this->postJson($this->inboxEndpoint.'/read')
        ->assertOk()
        ->assertJsonPath('data.marked_read', 0);

    Sanctum::actingAs($this->owner);

    $this->deleteJson($this->commentEndpoint.'/'.$commentId, [
        'device_id' => (string) $this->device->getKey(),
    ])->assertOk();

    expect(UserInboxNotification::query()->count())->toBe(0);
});

test('users cannot mark another users inbox notification as read', function (): void {
    $notification = UserInboxNotification::factory()->create([
        'user_id' => $this->reader->id,
        'actor_id' => $this->owner->id,
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'environment_id' => $this->environment->id,
        'environment_secret_id' => $this->secret->id,
    ]);

    Sanctum::actingAs($this->owner);

    $this->postJson($this->inboxEndpoint.'/'.$notification->id.'/read')
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('organization inbox endpoints are counted toward api usage limits', function (): void {
    Cache::flush();
    Date::setTestNow('2026-03-27 19:05:00');

    try {
        $notification = UserInboxNotification::factory()->create([
            'user_id' => $this->reader->id,
            'actor_id' => $this->owner->id,
            'organization_id' => $this->organization->id,
            'project_id' => $this->project->id,
            'environment_id' => $this->environment->id,
            'environment_secret_id' => $this->secret->id,
            'description' => 'Dana commented on "APP_KEY" in "production".',
            'payload' => [
                'target' => 'environment_variable_context',
                'project' => [
                    'id' => (string) $this->project->id,
                    'name' => $this->project->name,
                ],
                'environment' => [
                    'id' => (string) $this->environment->id,
                    'name' => $this->environment->name,
                    'type' => $this->environment->type->value,
                ],
                'variable' => [
                    'id' => (string) $this->secret->id,
                    'name' => $this->secret->name,
                ],
            ],
        ]);

        expect(
            UserInboxNotification::query()
                ->where('organization_id', $this->organization->id)
                ->where('user_id', $this->reader->id)
                ->whereNull('read_at')
                ->count()
        )->toBe(1);

        $token = $this->reader->createToken('Inbox Meter Test');
        $bucket = UsageDate::formatBucket(UsageDate::now());

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson($this->inboxUnreadCountEndpoint)
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson($this->inboxEndpoint)
            ->assertOk()
            ->assertJsonPath('data.entries.0.id', $notification->id);

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson($this->inboxEndpoint.'/'.$notification->id.'/read')
            ->assertOk()
            ->assertJsonPath('status', 'updated');

        $countCounterKey = UsageCacheKey::counter(
            bucket: $bucket,
            orgId: (string) $this->organization->id,
            tokenId: (string) $token->accessToken->getKey(),
            method: 'GET',
            endpoint: ltrim($this->inboxUnreadCountEndpoint, '/')
        );

        $listCounterKey = UsageCacheKey::counter(
            bucket: $bucket,
            orgId: (string) $this->organization->id,
            tokenId: (string) $token->accessToken->getKey(),
            method: 'GET',
            endpoint: ltrim($this->inboxEndpoint, '/')
        );

        $readCounterKey = UsageCacheKey::counter(
            bucket: $bucket,
            orgId: (string) $this->organization->id,
            tokenId: (string) $token->accessToken->getKey(),
            method: 'POST',
            endpoint: ltrim($this->inboxEndpoint.'/'.$notification->id.'/read', '/')
        );

        expect((int) Cache::store()->get($countCounterKey))->toBe(1)
            ->and((int) Cache::store()->get($listCounterKey))->toBe(1)
            ->and((int) Cache::store()->get($readCounterKey))->toBe(1);
    } finally {
        Date::setTestNow();
    }
});

test('prune inbox command removes stale read and unread notifications', function (): void {
    $recentUnread = UserInboxNotification::factory()->create([
        'user_id' => $this->reader->id,
        'organization_id' => $this->organization->id,
        'created_at' => now()->subDays(10),
        'updated_at' => now()->subDays(10),
        'read_at' => null,
    ]);

    $recentRead = UserInboxNotification::factory()->create([
        'user_id' => $this->reader->id,
        'organization_id' => $this->organization->id,
        'created_at' => now()->subDays(5),
        'updated_at' => now()->subDays(5),
        'read_at' => now()->subDays(5),
    ]);

    $staleUnread = UserInboxNotification::factory()->create([
        'user_id' => $this->reader->id,
        'organization_id' => $this->organization->id,
        'created_at' => now()->subDays(91),
        'updated_at' => now()->subDays(91),
        'read_at' => null,
    ]);

    $staleRead = UserInboxNotification::factory()->create([
        'user_id' => $this->reader->id,
        'organization_id' => $this->organization->id,
        'created_at' => now()->subDays(45),
        'updated_at' => now()->subDays(45),
        'read_at' => now()->subDays(45),
    ]);

    $this->artisan('notifications:prune-inbox')
        ->expectsOutput('Pruned 2 inbox notification(s).')
        ->assertExitCode(0);

    expect(UserInboxNotification::query()->pluck('id')->all())
        ->toContain($recentUnread->id)
        ->toContain($recentRead->id)
        ->not->toContain($staleUnread->id)
        ->not->toContain($staleRead->id);
});
