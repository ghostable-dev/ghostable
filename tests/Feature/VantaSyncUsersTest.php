<?php

use App\Environment\Enums\EnvironmentType;
use App\Integration\Entities\VantaSettings;
use App\Integration\Enums\IntegrationStatus;
use App\Integration\Integrations\Vanta\Actions\SyncUsersAction;
use App\Integration\Integrations\Vanta\Jobs\SyncUsers;
use App\Integration\Models\Integration;
use App\Organization\Actions\CreatePermissionOverride;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Enums\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('vanta.resource_id', 'abc123');
});

test('sync users pushes active vanta integrations', function () {
    Http::fake();

    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Demo Org', $owner);

    $member = $this->createUser('Member', 'member@example.com');
    $member->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER);

    Integration::factory()
        ->for($organization)
        ->vanta()
        ->create([
            'status' => IntegrationStatus::Active,
            'secure_settings' => ['access_token' => 'token-123'],
        ]);

    SyncUsers::dispatchSync();

    Http::assertSent(function ($request) use ($owner, $member) {
        if (! str_ends_with($request->url(), '/v1/resources/user_account')) {
            return false;
        }

        $data = $request->data();
        $resources = collect($data['resources'] ?? []);
        $resourcePayload = $resources->firstWhere('uniqueId', (string) $owner->id);
        $emails = $resources->pluck('email')->sort()->values()->all();

        return $request->method() === 'PUT'
            && ! array_key_exists('customProperties', $resourcePayload ?? [])
            && $data['resourceId'] === 'abc123'
            && $resourcePayload['fullName'] === $owner->name
            && $resourcePayload['accountName'] === $owner->email
            && $resourcePayload['permissionLevel'] === 'ADMIN'
            && $resourcePayload['mfaEnabled'] === false
            && $resourcePayload['mfaMethods'] === ['DISABLED']
            && $resourcePayload['status'] === 'ACTIVE'
            && str_contains($resourcePayload['externalUrl'] ?? '', '/organization/settings/members#user-'.(string) $owner->id)
            && $emails === collect([$owner->email, $member->email])->sort()->values()->all();
    });
});

test('sync users handles base url that already includes api version', function () {
    Http::fake();

    config()->set('vanta.resource_id', 'resource-v1');

    $owner = $this->createUser('Owner', 'owner-v1@example.com');
    $organization = $this->createOrganization('Versioned Org', $owner);

    Integration::factory()
        ->for($organization)
        ->vanta()
        ->create([
            'status' => IntegrationStatus::Active,
            'settings' => new VantaSettings(base_url: 'https://api.vanta.com/v1'),
            'secure_settings' => ['access_token' => 'token-v1'],
        ]);

    SyncUsers::dispatchSync();

    Http::assertSent(function ($request) {
        return $request->method() === 'PUT'
            && $request->url() === 'https://api.vanta.com/v1/resources/user_account';
    });
});

test('sync users includes mfa status when enabled', function () {
    Http::fake();

    $owner = $this->createUser('Owner', 'owner-mfa@example.com');
    $owner->forceFill([
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ])->save();

    $organization = $this->createOrganization('MFA Org', $owner);

    Integration::factory()
        ->for($organization)
        ->vanta()
        ->create([
            'status' => IntegrationStatus::Active,
            'secure_settings' => ['access_token' => 'token-123'],
        ]);

    SyncUsers::dispatchSync();

    Http::assertSent(function ($request) use ($owner) {
        if (! str_ends_with($request->url(), '/v1/resources/user_account')) {
            return false;
        }

        $resource = collect($request->data()['resources'] ?? [])
            ->firstWhere('uniqueId', (string) $owner->id);

        return $resource['mfaEnabled'] === true
            && $resource['mfaMethods'] === ['OTP'];
    });
});

test('sync users skips inactive vanta integrations', function () {
    Http::fake();

    $owner = $this->createUser('Owner', 'owner2@example.com');
    $organization = $this->createOrganization('Demo Org', $owner);

    Integration::factory()
        ->for($organization)
        ->vanta()
        ->create([
            'status' => IntegrationStatus::Failed,
            'secure_settings' => ['access_token' => 'token-456'],
        ]);

    SyncUsers::dispatchSync();

    Http::assertNothingSent();
});

test('sync users action skips inactive integration when invoked directly', function () {
    Http::fake();

    $owner = $this->createUser('Owner', 'direct@example.com');
    $organization = $this->createOrganization('Direct Org', $owner);

    $integration = Integration::factory()
        ->for($organization)
        ->vanta()
        ->create([
            'status' => IntegrationStatus::Failed,
            'secure_settings' => ['access_token' => 'token-999'],
        ]);

    app(SyncUsersAction::class)->handleForIntegration($integration);

    Http::assertNothingSent();
});

test('sync users can be disabled per integration', function () {
    Http::fake();

    $owner = $this->createUser('Owner', 'owner3@example.com');
    $organization = $this->createOrganization('Disabled Org', $owner);

    Integration::factory()
        ->for($organization)
        ->vanta()
        ->create([
            'status' => IntegrationStatus::Active,
            'settings' => new VantaSettings(sync_users_enabled: false),
            'secure_settings' => ['access_token' => 'token-789'],
        ]);

    SyncUsers::dispatchSync();

    Http::assertNothingSent();
});

test('sync users runs per organization integration', function () {
    Http::fake();

    config()->set('vanta.resource_id', 'shared-resource');

    $orgAOwner = $this->createUser('Owner A', 'owner-a@example.com');
    $orgA = $this->createOrganization('Org A', $orgAOwner);
    $orgAMember = $this->createUser('Member A', 'member-a@example.com');
    $orgAMember->organizationMembership()->assignToOrganization($orgA, OrganizationRole::DEVELOPER);
    $orgAReadOnly = $this->createUser('Read Only A', 'readonly-a@example.com');
    $orgAReadOnly->organizationMembership()->assignToOrganization($orgA, OrganizationRole::DEVELOPER_READ_ONLY);

    $orgBOwner = $this->createUser('Owner B', 'owner-b@example.com');
    $orgB = $this->createOrganization('Org B', $orgBOwner);
    $orgBMember = $this->createUser('Member B', 'member-b@example.com');
    $orgBMember->organizationMembership()->assignToOrganization($orgB, OrganizationRole::DEVELOPER);

    Integration::factory()
        ->for($orgA)
        ->vanta()
        ->create([
            'status' => IntegrationStatus::Active,
            'secure_settings' => ['access_token' => 'token-a'],
        ]);

    Integration::factory()
        ->for($orgB)
        ->vanta()
        ->create([
            'status' => IntegrationStatus::Active,
            'secure_settings' => ['access_token' => 'token-b'],
        ]);

    SyncUsers::dispatchSync();

    Http::assertSentCount(2);

    Http::assertSent(function ($request) use ($orgAOwner, $orgAMember, $orgAReadOnly) {
        if (($request->data()['resourceId'] ?? null) !== 'shared-resource') {
            return false;
        }

        $resources = collect($request->data()['resources']);
        $ids = $resources->pluck('uniqueId')->sort()->values()->all();
        $auth = $request->header('Authorization');
        $authHeader = is_array($auth) ? ($auth[0] ?? '') : (string) $auth;

        $readOnlyPayload = $resources->firstWhere('uniqueId', (string) $orgAReadOnly->id);

        return $ids === collect([$orgAOwner->id, $orgAMember->id, $orgAReadOnly->id])->sort()->values()->all()
            && $request->method() === 'PUT'
            && $authHeader === 'Bearer token-a'
            && ($readOnlyPayload['permissionLevel'] ?? null) === 'BASE';
    });

    Http::assertSent(function ($request) use ($orgBOwner, $orgBMember) {
        if (($request->data()['resourceId'] ?? null) !== 'shared-resource') {
            return false;
        }

        $resources = collect($request->data()['resources']);
        $ids = $resources->pluck('uniqueId')->sort()->values()->all();
        $auth = $request->header('Authorization');
        $authHeader = is_array($auth) ? ($auth[0] ?? '') : (string) $auth;

        return $ids === collect([$orgBOwner->id, $orgBMember->id])->sort()->values()->all()
            && $request->method() === 'PUT'
            && $authHeader === 'Bearer token-b';
    });
});

test('sync users collapses project overrides into editor permission level', function () {
    Http::fake();

    config()->set('vanta.resource_id', 'override-resource');

    $owner = $this->createUser('Owner Override', 'owner-override@example.com');
    $organization = $this->createOrganization('Override Org', $owner);

    $editor = $this->createUser('Override Editor', 'override-editor@example.com');
    $editor->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER_READ_ONLY);

    $project = $this->createProject('Override Project', $organization);
    resolve(CreatePermissionOverride::class)
        ->handle($editor, $project, OrganizationPermission::ManageProjectSettings);

    Integration::factory()
        ->for($organization)
        ->vanta()
        ->create([
            'status' => IntegrationStatus::Active,
            'settings' => new VantaSettings,
            'secure_settings' => ['access_token' => 'token-override'],
        ]);

    SyncUsers::dispatchSync();

    Http::assertSent(function ($request) use ($editor) {
        if (($request->data()['resourceId'] ?? null) !== 'override-resource') {
            return false;
        }

        $resource = collect($request->data()['resources'] ?? [])
            ->firstWhere('uniqueId', (string) $editor->id);

        return $resource['permissionLevel'] === 'EDITOR';
    });
});

test('sync users keeps read-only overrides as base permission level', function () {
    Http::fake();

    config()->set('vanta.resource_id', 'read-resource');

    $owner = $this->createUser('Owner Read', 'owner-read@example.com');
    $organization = $this->createOrganization('Read Org', $owner);

    $reader = $this->createUser('Override Reader', 'override-reader@example.com');
    $reader->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER_READ_ONLY);

    $project = $this->createProject('Read Project', $organization);
    $environment = $this->createEnvironment('Read Env', EnvironmentType::PRODUCTION, $project);

    resolve(CreatePermissionOverride::class)
        ->handle($reader, $environment, OrganizationPermission::ViewVariables);

    Integration::factory()
        ->for($organization)
        ->vanta()
        ->create([
            'status' => IntegrationStatus::Active,
            'settings' => new VantaSettings,
            'secure_settings' => ['access_token' => 'token-read'],
        ]);

    SyncUsers::dispatchSync();

    Http::assertSent(function ($request) use ($reader) {
        if (($request->data()['resourceId'] ?? null) !== 'read-resource') {
            return false;
        }

        $resource = collect($request->data()['resources'] ?? [])
            ->firstWhere('uniqueId', (string) $reader->id);

        return $resource['permissionLevel'] === 'BASE';
    });
});
