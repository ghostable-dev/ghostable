<?php

declare(strict_types=1);

use App\Api\Usage\Support\UsageCacheKey;
use App\Api\Usage\Support\UsageDate;
use App\Core\Models\Activity;
use App\Crypto\Models\Device;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Organization\Actions\CreatePermissionOverride;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Enums\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->owner = $this->createUser('Dana', 'dana@example.com');
    $this->organization = $this->createOrganization('Ghostable', $this->owner);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);

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

    $this->firstVersion = EnvironmentSecretVersion::query()->create([
        'environment_secret_id' => $this->secret->id,
        'version' => 1,
        'name' => 'APP_KEY',
        'ciphertext' => $this->secret->ciphertext,
        'nonce' => $this->secret->nonce,
        'alg' => $this->secret->alg,
        'aad' => $this->secret->aad,
        'claims' => $this->secret->claims,
        'client_sig' => $this->secret->client_sig,
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fp-current',
        'metadata' => [],
        'line_bytes' => 16,
        'is_commented' => false,
        'changed_by' => $this->owner->id,
        'created_at' => now()->subHour(),
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

    $this->makeEncryptedBody = function (string $plaintext, string $scope): array {
        $payload = [
            'ciphertext' => base64_encode($plaintext),
            'nonce' => base64_encode(random_bytes(24)),
            'alg' => 'xchacha20-poly1305',
            'aad' => [
                'env' => (string) $this->environment->id,
                'variable' => $this->secret->name,
                'scope' => $scope,
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

    $this->contextEndpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/variables/%s/context',
        $this->project->id,
        $this->environment->name,
        $this->secret->name,
    );

    $this->noteEndpoint = $this->contextEndpoint.'/note';
    $this->commentEndpoint = $this->contextEndpoint.'/comments';
    $this->pushEndpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/push',
        $this->project->id,
        $this->environment->name,
    );
    $this->historyEndpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/variables/%s/history',
        $this->project->id,
        $this->environment->name,
        $this->secret->name,
    );
});

test('context endpoints store encrypted notes and comments without logging plaintext', function (): void {
    Sanctum::actingAs($this->owner);

    $notePlaintext = 'Rotate the key after the upstream outage is resolved.';
    $updatedNotePlaintext = 'Key rotated after the outage and rollout verification.';
    $commentPlaintext = 'Confirmed with release engineering before rotating.';

    $notePayload = ($this->makeEncryptedBody)($notePlaintext, 'note');
    $updatedNotePayload = ($this->makeEncryptedBody)($updatedNotePlaintext, 'note');
    $commentPayload = ($this->makeEncryptedBody)($commentPlaintext, 'comment');

    $this->putJson($this->noteEndpoint, [
        'device_id' => (string) $this->device->getKey(),
        'note' => $notePayload,
    ])
        ->assertOk()
        ->assertJsonPath('status', 'updated');

    $this->putJson($this->noteEndpoint, [
        'device_id' => (string) $this->device->getKey(),
        'note' => $updatedNotePayload,
    ])
        ->assertOk()
        ->assertJsonPath('status', 'updated');

    $this->postJson($this->commentEndpoint, [
        'device_id' => (string) $this->device->getKey(),
        'comment' => $commentPayload,
    ])
        ->assertCreated()
        ->assertJsonPath('status', 'created');

    $response = $this->getJson($this->contextEndpoint)
        ->assertOk()
        ->assertJsonPath('data.note.body.ciphertext', $updatedNotePayload['ciphertext'])
        ->assertJsonPath('data.comments.0.body.ciphertext', $commentPayload['ciphertext']);

    expect(DB::table('environment_variable_notes')->count())->toBe(1)
        ->and(DB::table('environment_variable_comments')->count())->toBe(1);

    $rawNote = (array) DB::table('environment_variable_notes')->first();
    $rawComment = (array) DB::table('environment_variable_comments')->first();

    expect(json_encode($rawNote, JSON_THROW_ON_ERROR))->not->toContain($updatedNotePlaintext)
        ->and(json_encode($rawComment, JSON_THROW_ON_ERROR))->not->toContain($commentPlaintext);

    $activities = Activity::query()
        ->whereIn('event', ['context_note_updated', 'context_comment_added'])
        ->orderBy('id')
        ->get();

    expect($activities)->toHaveCount(3);

    foreach ($activities as $activity) {
        $propertiesJson = json_encode($activity->properties, JSON_THROW_ON_ERROR);

        expect($activity->description)->not->toContain($notePlaintext)
            ->and($activity->description)->not->toContain($updatedNotePlaintext)
            ->and($activity->description)->not->toContain($commentPlaintext)
            ->and($propertiesJson)->not->toContain($notePlaintext)
            ->and($propertiesJson)->not->toContain($updatedNotePlaintext)
            ->and($propertiesJson)->not->toContain($commentPlaintext)
            ->and($propertiesJson)->not->toContain($updatedNotePayload['ciphertext'])
            ->and($propertiesJson)->not->toContain($commentPayload['ciphertext']);
    }

    $responseBody = $response->getContent();

    expect($responseBody)->not->toContain($updatedNotePlaintext)
        ->and($responseBody)->not->toContain($commentPlaintext);
});

test('users can delete their own comments without logging plaintext', function (): void {
    Sanctum::actingAs($this->owner);

    $commentPlaintext = 'Verified with ops before rotating the key.';
    $commentPayload = ($this->makeEncryptedBody)($commentPlaintext, 'comment');

    $commentId = $this->postJson($this->commentEndpoint, [
        'device_id' => (string) $this->device->getKey(),
        'comment' => $commentPayload,
    ])
        ->assertCreated()
        ->json('data.comment_id');

    expect($commentId)->toBeString();

    $this->deleteJson($this->commentEndpoint.'/'.$commentId, [
        'device_id' => (string) $this->device->getKey(),
    ])
        ->assertOk()
        ->assertJsonPath('status', 'deleted');

    $this->getJson($this->contextEndpoint)
        ->assertOk()
        ->assertJsonCount(0, 'data.comments');

    expect(DB::table('environment_variable_comments')->count())->toBe(0);

    $deleteActivity = Activity::query()
        ->where('event', 'context_comment_deleted')
        ->latest('id')
        ->first();

    expect($deleteActivity)->not->toBeNull();

    $propertiesJson = json_encode($deleteActivity->properties, JSON_THROW_ON_ERROR);

    expect($deleteActivity->description)->not->toContain($commentPlaintext)
        ->and($propertiesJson)->not->toContain($commentPlaintext)
        ->and($propertiesJson)->not->toContain($commentPayload['ciphertext']);
});

test('users cannot delete comments created by another member', function (): void {
    $member = $this->createUser('Ray', 'ray@example.com');
    $member->organizationMembership()->assignToOrganization($this->organization, OrganizationRole::DEVELOPER);

    $memberKeypair = sodium_crypto_sign_keypair();
    $memberPublicKey = sodium_crypto_sign_publickey($memberKeypair);

    $memberDevice = Device::factory()->for($member)->create([
        'active' => true,
        'revoked_at' => null,
        'client_type' => 'desktop',
        'public_signing_key' => base64_encode($memberPublicKey),
    ]);

    Sanctum::actingAs($this->owner);

    $commentId = $this->postJson($this->commentEndpoint, [
        'device_id' => (string) $this->device->getKey(),
        'comment' => ($this->makeEncryptedBody)('Owner comment.', 'comment'),
    ])
        ->assertCreated()
        ->json('data.comment_id');

    Sanctum::actingAs($member);

    $this->deleteJson($this->commentEndpoint.'/'.$commentId, [
        'device_id' => (string) $memberDevice->getKey(),
    ])
        ->assertForbidden()
        ->assertJsonPath('message', 'You may only delete your own comments.');

    expect(DB::table('environment_variable_comments')->count())->toBe(1);
});

test('comment create and delete requests are counted toward api usage limits', function (): void {
    Cache::flush();
    Date::setTestNow('2026-03-27 16:15:00');

    try {
        $token = $this->owner->createToken('Desktop Context Test');
        $bucket = UsageDate::formatBucket(UsageDate::now());
        $commentPayload = ($this->makeEncryptedBody)('Usage metering comment.', 'comment');

        $commentId = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson($this->commentEndpoint, [
                'device_id' => (string) $this->device->getKey(),
                'comment' => $commentPayload,
            ])
            ->assertCreated()
            ->json('data.comment_id');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->deleteJson($this->commentEndpoint.'/'.$commentId.'?device_id='.$this->device->getKey())
            ->assertOk()
            ->assertJsonPath('status', 'deleted');

        $postCounterKey = UsageCacheKey::counter(
            bucket: $bucket,
            orgId: (string) $this->organization->id,
            tokenId: (string) $token->accessToken->getKey(),
            method: 'POST',
            endpoint: ltrim($this->commentEndpoint, '/')
        );

        $deleteCounterKey = UsageCacheKey::counter(
            bucket: $bucket,
            orgId: (string) $this->organization->id,
            tokenId: (string) $token->accessToken->getKey(),
            method: 'DELETE',
            endpoint: ltrim($this->commentEndpoint.'/'.$commentId, '/')
        );

        expect((int) Cache::store()->get($postCounterKey))->toBe(1)
            ->and((int) Cache::store()->get($deleteCounterKey))->toBe(1);
    } finally {
        Date::setTestNow();
    }
});

test('context permissions are enforced separately from variable visibility', function (): void {
    $member = $this->createUser('Ray', 'ray@example.com');
    $member->organizationMembership()->assignToOrganization($this->organization, OrganizationRole::DEVELOPER_READ_ONLY);

    $this->project->update(['is_restricted' => true]);
    $this->environment->update(['is_restricted' => true]);

    app(CreatePermissionOverride::class)->handle(
        $member,
        $this->environment,
        OrganizationPermission::ViewVariables,
        $this->owner
    );

    $memberKeypair = sodium_crypto_sign_keypair();
    $memberSecretKey = sodium_crypto_sign_secretkey($memberKeypair);
    $memberPublicKey = sodium_crypto_sign_publickey($memberKeypair);

    $memberDevice = Device::factory()->for($member)->create([
        'active' => true,
        'revoked_at' => null,
        'client_type' => 'desktop',
        'public_signing_key' => base64_encode($memberPublicKey),
    ]);

    $signMemberPayload = function (array $payload) use ($memberSecretKey): array {
        $payloadToSign = $payload;
        unset($payloadToSign['client_sig']);

        $payloadJson = json_encode(
            $payloadToSign,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $payload['client_sig'] = base64_encode(
            sodium_crypto_sign_detached($payloadJson, $memberSecretKey)
        );

        return $payload;
    };

    Sanctum::actingAs($member);

    $this->getJson($this->contextEndpoint)
        ->assertForbidden();

    app(CreatePermissionOverride::class)->handle(
        $member,
        $this->environment,
        OrganizationPermission::ViewContext,
        $this->owner
    );

    $this->getJson($this->contextEndpoint)
        ->assertOk()
        ->assertJsonPath('data.permissions.edit_note', false)
        ->assertJsonPath('data.permissions.comment', false);

    $notePayload = $signMemberPayload([
        'ciphertext' => base64_encode('note'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['scope' => 'note'],
        'claims' => ['meta' => ['body_length' => 4]],
        'client_sig' => 'pending',
    ]);

    $commentPayload = $signMemberPayload([
        'ciphertext' => base64_encode('comment'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['scope' => 'comment'],
        'claims' => ['meta' => ['body_length' => 7]],
        'client_sig' => 'pending',
    ]);

    $this->putJson($this->noteEndpoint, [
        'device_id' => (string) $memberDevice->getKey(),
        'note' => $notePayload,
    ])
        ->assertForbidden();

    $this->postJson($this->commentEndpoint, [
        'device_id' => (string) $memberDevice->getKey(),
        'comment' => $commentPayload,
    ])
        ->assertForbidden();
});

test('push ties encrypted change notes to the correct version and hides them without permission', function (): void {
    Sanctum::actingAs($this->owner);

    $signSecretPayload = function (array $payload): array {
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

    $makeSecretPayload = function (string $name, array $overrides = []) use ($signSecretPayload): array {
        $base = [
            'name' => $name,
            'ciphertext' => "ciphertext-{$name}-v2",
            'nonce' => "nonce-{$name}-v2",
            'alg' => 'xchacha20-poly1305',
            'aad' => [
                'org' => (string) $this->organization->id,
                'project' => (string) $this->environment->project->id,
                'env' => (string) $this->environment->id,
                'name' => $name,
            ],
            'claims' => [
                'hmac' => "hmac-{$name}-v2",
                'meta' => [
                    'value_length' => 12,
                    'is_vapor_secret' => false,
                    'is_commented' => false,
                ],
            ],
            'if_version' => 1,
            'client_sig' => 'pending',
        ];

        return $signSecretPayload(array_replace_recursive($base, $overrides));
    };

    $changeNotePlaintext = 'Rotated after upstream credentials were exposed.';
    $changeNotePayload = ($this->makeEncryptedBody)($changeNotePlaintext, 'change_note');

    $this->postJson($this->pushEndpoint, [
        'device_id' => (string) $this->device->getKey(),
        'secrets' => [
            $makeSecretPayload('APP_KEY', [
                'change_note' => $changeNotePayload,
            ]),
        ],
    ])
        ->assertOk()
        ->assertJsonFragment(['updated' => 1]);

    $secret = $this->secret->fresh(['latestVersion.changeNote']);

    expect($secret->version)->toBe(2)
        ->and($secret->latestVersion)->not->toBeNull()
        ->and($secret->latestVersion->changeNote)->not->toBeNull()
        ->and($secret->latestVersion->changeNote->environment_secret_version_id)->toBe($secret->latestVersion->id);

    $this->getJson($this->historyEndpoint)
        ->assertOk()
        ->assertJsonPath('data.entries.0.version', 2)
        ->assertJsonPath('data.entries.0.change_note.body.ciphertext', $changeNotePayload['ciphertext'])
        ->assertJsonPath('data.meta.permissions.view_version_change_notes', true);

    $reasonActivity = Activity::query()
        ->where('event', 'updated_with_reason')
        ->latest('id')
        ->first();

    expect($reasonActivity)->not->toBeNull();
    expect($reasonActivity->description)->not->toContain($changeNotePlaintext)
        ->and(json_encode($reasonActivity->properties, JSON_THROW_ON_ERROR))->not->toContain($changeNotePlaintext)
        ->and(json_encode($reasonActivity->properties, JSON_THROW_ON_ERROR))->not->toContain($changeNotePayload['ciphertext']);

    $member = $this->createUser('Egon', 'egon@example.com');
    $member->organizationMembership()->assignToOrganization($this->organization, OrganizationRole::DEVELOPER_READ_ONLY);

    $this->project->update(['is_restricted' => true]);
    $this->environment->update(['is_restricted' => true]);

    app(CreatePermissionOverride::class)->handle(
        $member,
        $this->environment,
        OrganizationPermission::ViewVariables,
        $this->owner
    );

    Sanctum::actingAs($member);

    $this->getJson($this->historyEndpoint)
        ->assertOk()
        ->assertJsonPath('data.entries.0.change_note', null)
        ->assertJsonPath('data.meta.permissions.view_version_change_notes', false);
});
