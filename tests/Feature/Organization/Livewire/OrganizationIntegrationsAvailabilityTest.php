<?php

use App\Billing\Enums\Plan;
use App\Integration\Models\IntegrationClient;
use App\Organization\Livewire\OrganizationIntegrationsSettings;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createIntegrationClientForOrgWithStatus(Organization $organization, string $key, string $publishStatus): IntegrationClient
{
    return IntegrationClient::query()->create([
        'name' => Str::title(str_replace('-', ' ', $key)),
        'key' => $key,
        'client_id' => Str::random(32),
        'client_secret_hash' => Hash::make(Str::random(64)),
        'redirect_uris' => ['https://example.com/oauth/callback'],
        'default_scopes' => ['organization.read'],
        'status' => 'active',
        'owner_organization_id' => $organization->id,
        'publish_status' => $publishStatus,
    ]);
}

test('only published partner integrations appear for paid organizations', function () {
    $paidUser = $this->createUser('Paid Owner', 'paid@example.com');
    $paidOrg = $this->createOrganization('Paid Org', $paidUser, planOverride: Plan::STANDARD);

    $partnerOwner = $this->createUser('Partner Owner', 'partner@example.com');
    $partnerOrg = $this->createOrganization('Partner Org', $partnerOwner, planOverride: Plan::STANDARD);
    $partnerOrg->forceFill(['is_partner' => true])->save();

    $nonPartnerOwner = $this->createUser('Non Partner Owner', 'nonpartner@example.com');
    $nonPartnerOrg = $this->createOrganization('Non Partner Org', $nonPartnerOwner, planOverride: Plan::STANDARD);

    $partnerPublished = createIntegrationClientForOrgWithStatus(
        $partnerOrg,
        'partner-published',
        IntegrationClient::PUBLISH_STATUS_PUBLISHED
    );
    createIntegrationClientForOrgWithStatus($partnerOrg, 'partner-draft', IntegrationClient::PUBLISH_STATUS_DRAFT);
    createIntegrationClientForOrgWithStatus($nonPartnerOrg, 'non-partner-published', IntegrationClient::PUBLISH_STATUS_PUBLISHED);
    createIntegrationClientForOrgWithStatus($paidOrg, 'own-published', IntegrationClient::PUBLISH_STATUS_PUBLISHED);

    Livewire::actingAs($paidUser);

    $component = Livewire::test(OrganizationIntegrationsSettings::class);
    $publishedClients = $component->get('publishedIntegrationClients');

    expect($publishedClients->pluck('id')->all())
        ->toEqualCanonicalizing([$partnerPublished->id]);
});
