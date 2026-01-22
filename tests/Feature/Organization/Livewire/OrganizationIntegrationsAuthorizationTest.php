<?php

use App\Billing\Enums\Plan;
use App\Integration\Models\IntegrationClient;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Livewire\OrganizationIntegrationsCreate;
use App\Organization\Livewire\OrganizationIntegrationsEdit;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createIntegrationClientForOrg(Organization $organization, string $name = 'Test Integration'): IntegrationClient
{
    return IntegrationClient::query()->create([
        'name' => $name,
        'key' => Str::slug($name),
        'client_id' => Str::random(32),
        'client_secret_hash' => Hash::make(Str::random(64)),
        'redirect_uris' => ['https://example.com/oauth/callback'],
        'default_scopes' => ['organization.read'],
        'status' => 'active',
        'owner_organization_id' => $organization->id,
        'publish_status' => IntegrationClient::PUBLISH_STATUS_DRAFT,
        'description' => 'Integration description.',
    ]);
}

test('non-admins cannot create integration clients', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner, planOverride: Plan::STANDARD);

    $developer = $this->createUser('Dev', 'dev@example.com');
    $developer->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER);

    $this->actingAs($developer);

    Livewire::test(OrganizationIntegrationsCreate::class)
        ->call('createIntegrationClient')
        ->assertForbidden();
});

test('non-admins cannot update integration clients', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner, planOverride: Plan::STANDARD);
    $client = createIntegrationClientForOrg($organization);

    $developer = $this->createUser('Dev', 'dev@example.com');
    $developer->organizationMembership()->assignToOrganization($organization, OrganizationRole::DEVELOPER);

    $this->actingAs($developer);

    Livewire::test(OrganizationIntegrationsEdit::class, ['client' => $client->id])
        ->call('updateIntegrationClient')
        ->assertForbidden();
});

test('admins can create integration clients', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner, planOverride: Plan::STANDARD);

    $this->actingAs($owner);

    Livewire::test(OrganizationIntegrationsCreate::class)
        ->set('name', 'Acme Custom')
        ->set('key', 'acme-custom')
        ->set('redirectUris', 'https://example.com/oauth/callback')
        ->set('defaultScopes', ['organization.read'])
        ->set('description', 'Custom integration description.')
        ->set('logo', UploadedFile::fake()->image('logo.png', 512, 512))
        ->call('createIntegrationClient')
        ->assertHasNoErrors();

    expect(IntegrationClient::query()->where('name', 'Acme Custom')->exists())->toBeTrue();
});

test('admins can update integration clients', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Acme', $owner, planOverride: Plan::STANDARD);
    $client = createIntegrationClientForOrg($organization, 'Original Name');

    $this->actingAs($owner);

    Livewire::test(OrganizationIntegrationsEdit::class, ['client' => $client->id])
        ->set('name', 'Updated Name')
        ->set('redirectUris', 'https://example.com/updated/callback')
        ->set('defaultScopes', ['organization.read'])
        ->set('description', 'Updated description.')
        ->set('logo', UploadedFile::fake()->image('logo.png', 512, 512))
        ->call('updateIntegrationClient')
        ->assertHasNoErrors();

    expect($client->fresh()->name)->toBe('Updated Name');
});
