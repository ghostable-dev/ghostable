<?php

declare(strict_types=1);

use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Organization\Enums\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->owner = $this->createUser('Dana', 'dana@ghostable.com');
    $this->recipient = $this->createUser('Louis', 'louis@ghostable.com');
    $this->observer = $this->createUser('Walter', 'walter@ghostable.com');

    $this->organization = $this->createOrganization('Ghostbusters', $this->owner, [
        $this->recipient,
        $this->observer,
    ]);

    $this->observer->organizations()->updateExistingPivot($this->organization->id, [
        'role' => OrganizationRole::DEVELOPER_READ_ONLY->value,
    ]);
    $this->observer->organizationMembership()->clearMembershipCache($this->organization);

    $this->organization->features = $this->organization->features->withOverrides([
        'guided_key_reshare_v2' => true,
    ]);
    $this->organization->save();

    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);

    $this->ownerDevice = $this->createDevice($this->owner, 'Dana MBP');
    $this->recipientDevice = $this->createDevice($this->recipient, 'Louis MBP');

    $environmentKey = $this->createEnvironmentKeyWithEnvelope(
        environment: $this->environment,
        createdByDevice: $this->ownerDevice,
        recipients: [[
            'id' => (string) $this->ownerDevice->id,
            'type' => 'device',
            'label' => 'Dana MBP',
        ]],
    );

    $this->requestModel = EnvironmentKeyReshareRequest::query()->create([
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'environment_id' => $this->environment->id,
        'required_key_version' => $environmentKey->version,
        'target_user_id' => $this->recipient->id,
        'target_device_id' => $this->recipientDevice->id,
        'status' => EnvironmentKeyReshareRequestStatus::Pending,
        'trigger_source' => 'device_link',
    ]);
});

test('actors and recipients can list pending key re-share requests by role', function (): void {
    Sanctum::actingAs($this->owner);

    $this->getJson(
        "/api/v2/organizations/{$this->organization->id}/key-reshare-requests?role=actor&status=pending"
    )
        ->assertOk()
        ->assertJsonPath('data.0.id', (string) $this->requestModel->id)
        ->assertJsonPath('data.0.attributes.status', 'pending');

    Sanctum::actingAs($this->recipient);

    $this->getJson(
        "/api/v2/organizations/{$this->organization->id}/key-reshare-requests?role=recipient&status=pending"
    )
        ->assertOk()
        ->assertJsonPath('data.0.id', (string) $this->requestModel->id)
        ->assertJsonPath('data.0.attributes.target_user_id', (string) $this->recipient->id);

    Sanctum::actingAs($this->observer);

    $this->getJson(
        "/api/v2/organizations/{$this->organization->id}/key-reshare-requests?role=actor&status=pending"
    )
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('show endpoint allows only actor or recipient visibility', function (): void {
    Sanctum::actingAs($this->owner);

    $this->getJson(
        "/api/v2/organizations/{$this->organization->id}/key-reshare-requests/{$this->requestModel->id}"
    )
        ->assertOk()
        ->assertJsonPath('data.id', (string) $this->requestModel->id);

    Sanctum::actingAs($this->recipient);

    $this->getJson(
        "/api/v2/organizations/{$this->organization->id}/key-reshare-requests/{$this->requestModel->id}"
    )
        ->assertOk()
        ->assertJsonPath('data.id', (string) $this->requestModel->id);

    Sanctum::actingAs($this->observer);

    $this->getJson(
        "/api/v2/organizations/{$this->organization->id}/key-reshare-requests/{$this->requestModel->id}"
    )
        ->assertForbidden();
});

test('list and show endpoints remain available even if guided key re-share override is false', function (): void {
    $this->organization->features = $this->organization->features->withOverrides([
        'guided_key_reshare_v2' => false,
    ]);
    $this->organization->save();

    Sanctum::actingAs($this->owner);

    $this->getJson(
        "/api/v2/organizations/{$this->organization->id}/key-reshare-requests?role=actor&status=pending"
    )
        ->assertOk()
        ->assertJsonPath('data.0.id', (string) $this->requestModel->id);

    $this->getJson(
        "/api/v2/organizations/{$this->organization->id}/key-reshare-requests/{$this->requestModel->id}"
    )
        ->assertOk()
        ->assertJsonPath('data.id', (string) $this->requestModel->id);
});
