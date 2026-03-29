<?php

declare(strict_types=1);

use App\Environment\Actions\ManageEnvironmentKeyReshareRequests;
use App\Environment\Enums\EnvironmentType;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Livewire\OrganizationKeyReshareRequestsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('actor sees pending key re-share requests in organization queue', function (): void {
    $owner = $this->createUser('Dana', 'dana@ghostbusters.com');
    $recipient = $this->createUser('Louis', 'louis@ghostbusters.com');
    $organization = $this->createOrganization('Ghostbusters', $owner, [$recipient]);

    $organization->features = $organization->features->withOverrides([
        'guided_key_reshare_v2' => true,
    ]);
    $organization->save();

    $project = $this->createProject('Containment Unit', $organization);
    $environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    $ownerDevice = $this->createDevice($owner, 'Owner Mac');
    $this->createEnvironmentKeyWithEnvelope(
        environment: $environment,
        createdByDevice: $ownerDevice,
        recipients: [[
            'id' => (string) $ownerDevice->id,
            'type' => 'device',
            'label' => 'Owner device',
        ]]
    );

    $recipientDevice = $this->createDevice($recipient, 'Recipient Mac');

    app(ManageEnvironmentKeyReshareRequests::class)->ensurePendingForEnvironmentDevice(
        environment: $environment,
        device: $recipientDevice,
        triggerSource: 'manual',
        actor: $owner,
        notifyActors: false,
    );

    $this->actingAs($owner);

    Livewire::test(OrganizationKeyReshareRequestsManager::class)
        ->assertSee('Environment Key Re-share Queue')
        ->assertSee('Containment Unit')
        ->assertSee('production')
        ->assertSee('CLI command')
        ->assertSee('ghostable env reshare fulfill')
        ->assertSee('Open in desktop')
        ->assertSeeHtml('ghostable-local://organization/'.$organization->id.'/key-reshare/');
});

test('recipient sees waiting state in organization key re-share queue', function (): void {
    $owner = $this->createUser('Ray', 'ray@ghostbusters.com');
    $recipient = $this->createUser('Winston', 'winston@ghostbusters.com');
    $organization = $this->createOrganization('Ghostbusters', $owner);
    $recipient->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER_READ_ONLY);

    $organization->features = $organization->features->withOverrides([
        'guided_key_reshare_v2' => true,
    ]);
    $organization->save();

    $project = $this->createProject('Containment Unit', $organization);
    $environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    $ownerDevice = $this->createDevice($owner, 'Owner Mac');
    $this->createEnvironmentKeyWithEnvelope(
        environment: $environment,
        createdByDevice: $ownerDevice,
        recipients: [[
            'id' => (string) $ownerDevice->id,
            'type' => 'device',
            'label' => 'Owner device',
        ]]
    );

    $recipientDevice = $this->createDevice($recipient, 'Recipient Mac');

    app(ManageEnvironmentKeyReshareRequests::class)->ensurePendingForEnvironmentDevice(
        environment: $environment,
        device: $recipientDevice,
        triggerSource: 'manual',
        actor: $owner,
        notifyActors: false,
    );

    $this->actingAs($recipient);

    Livewire::test(OrganizationKeyReshareRequestsManager::class)
        ->assertSee('Environment Key Re-share Queue')
        ->assertSee('Waiting for an environment manager to re-share keys.');
});
