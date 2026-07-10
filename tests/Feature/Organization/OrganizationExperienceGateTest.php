<?php

use App\Environment\Actions\Token\CreateDeploymentToken as CreateDeploymentTokenAction;
use App\Environment\Enums\EnvironmentType;
use App\Integration\Enums\IntegrationDirection;
use App\Integration\Models\Integration;
use App\Integration\Models\IntegrationAuthorizationCode;
use App\Integration\Models\IntegrationClient;
use App\Integration\Models\IntegrationToken;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('defaults organizations to the legacy project experience', function (): void {
    $organization = Organization::factory()->create();

    expect($organization->desktop_licensing_enabled)->toBeFalse()
        ->and($organization->usesDesktopLicensing())->toBeFalse()
        ->and($organization->usesLegacyProjectExperience())->toBeTrue();
});

it('shows the licensing dashboard and hides legacy project content for migrated organizations', function (): void {
    $user = $this->createUser('License Owner', 'license-owner@example.com');
    $organization = $this->createOrganization('Licensed Org', $user);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();

    $this->actingAs($user->fresh());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Desktop licensing is enabled')
        ->assertSee('View licensing')
        ->assertDontSee('Recent Projects')
        ->assertDontSee('Variable Promotion Requests');

    $this->get(route('organization.settings.billing'))
        ->assertOk()
        ->assertSee('Desktop licensing is enabled')
        ->assertSee('Personal')
        ->assertSee('Team 5')
        ->assertSee('Team 10')
        ->assertDontSee('$29')
        ->assertDontSee('$99');
});

it('blocks legacy project and integration web routes for migrated organizations', function (): void {
    $user = $this->createUser('License Owner', 'license-owner@example.com');
    $organization = $this->createOrganization('Licensed Org', $user);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();
    $project = $this->createProject('Legacy Project', $organization);
    $environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    $this->actingAs($user->fresh());

    $this->get(route('projects'))->assertForbidden();
    $this->get(route('project.environments', $project))->assertForbidden();
    $this->get(route('environment.variables', $environment))->assertForbidden();
    $this->get(route('organization.settings.integrations'))->assertForbidden();

    expect($user->can('create', [Project::class, $organization]))->toBeFalse()
        ->and($user->can('view', $project))->toBeFalse();
});

it('keeps legacy project and billing surfaces available for legacy organizations', function (): void {
    $user = $this->createUser('Legacy Owner', 'legacy-owner@example.com');
    $organization = $this->createOrganization('Legacy Org', $user);
    $this->createProject('Legacy Project', $organization);

    $this->actingAs($user->fresh());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Recent Projects')
        ->assertDontSee('Desktop licensing is enabled');

    $this->get(route('projects'))->assertOk();

    $this->get(route('organization.settings.billing'))
        ->assertOk()
        ->assertSee('$29')
        ->assertSee('$99')
        ->assertDontSee('Checkout coming soon');
});

it('filters migrated organizations out of v2 organization listings and blocks direct v2 access', function (): void {
    $user = $this->createUser('API User', 'api-user@example.com');
    $legacyOrganization = $this->createOrganization('Legacy API Org', $user);
    $licensedOrganization = $this->createOrganization('Licensed API Org', $user);
    $licensedOrganization->forceFill(['desktop_licensing_enabled' => true])->save();
    $project = $this->createProject('Hidden Project', $licensedOrganization);

    Sanctum::actingAs($user);

    $this->getJson('/api/v2/organizations')
        ->assertOk()
        ->assertJsonFragment(['name' => $legacyOrganization->name])
        ->assertJsonMissing(['name' => $licensedOrganization->name]);

    $this->getJson('/api/v2/owned-organizations')
        ->assertOk()
        ->assertJsonFragment(['name' => $legacyOrganization->name])
        ->assertJsonMissing(['name' => $licensedOrganization->name]);

    $this->getJson("/api/v2/organizations/{$licensedOrganization->getKey()}")->assertForbidden();
    $this->getJson("/api/v2/organizations/{$licensedOrganization->getKey()}/projects")->assertForbidden();
    $this->getJson("/api/v2/projects/{$project->getKey()}")->assertForbidden();
});

it('blocks v2 deployment tokens for migrated organizations', function (): void {
    $user = $this->createUser('Deploy User', 'deploy-user@example.com');
    $organization = $this->createOrganization('Licensed Deploy Org', $user);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();
    $project = $this->createProject('Hidden Deploy Project', $organization);
    $environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    $tokenResult = app(CreateDeploymentTokenAction::class)->handle(
        name: 'deploy',
        environment: $environment,
        publicKey: base64_encode(random_bytes(32)),
        user: $user,
        recipient: [
            'edek_b64' => 'b64:'.base64_encode(json_encode([
                'ciphertext_b64' => 'b64:'.base64_encode(random_bytes(32)),
                'nonce_b64' => 'b64:'.base64_encode(random_bytes(24)),
                'alg' => 'xchacha20-poly1305',
                'aad_b64' => null,
                'from_ephemeral_public_key' => 'b64:'.base64_encode(random_bytes(32)),
            ], JSON_THROW_ON_ERROR)),
        ],
    );

    $this->withHeaders(['Authorization' => 'Bearer '.$tokenResult->plainTextSecret])
        ->getJson('/api/v2/ci/deploy')
        ->assertForbidden();
});

it('blocks integration oauth and api tokens for migrated organizations', function (): void {
    $user = $this->createUser('Integration User', 'integration-user@example.com');
    $organization = $this->createOrganization('Licensed Integration Org', $user);
    $organization->forceFill(['desktop_licensing_enabled' => true])->save();
    $clientSecret = 'integration-secret';
    $authorizationCode = 'authorization-code';
    $refreshToken = 'refresh-token';
    $accessToken = 'access-token';

    $client = IntegrationClient::query()->create([
        'name' => 'Partner App',
        'key' => 'partner-app',
        'client_id' => 'partner-client',
        'client_secret_hash' => Hash::make($clientSecret),
        'redirect_uris' => ['https://partner.test/callback'],
        'default_scopes' => ['organization:read'],
        'status' => 'active',
    ]);

    IntegrationAuthorizationCode::query()->create([
        'integration_client_id' => $client->getKey(),
        'organization_id' => $organization->getKey(),
        'user_id' => $user->getKey(),
        'code_hash' => hash('sha256', $authorizationCode),
        'scopes' => ['organization:read'],
        'redirect_uri' => 'https://partner.test/callback',
        'expires_at' => now()->addMinutes(5),
    ]);

    $integration = Integration::factory()->create([
        'organization_id' => $organization->getKey(),
        'key' => 'partner-app',
        'direction' => IntegrationDirection::Incoming,
        'integration_client_id' => $client->getKey(),
    ]);

    IntegrationToken::query()->create([
        'integration_client_id' => $client->getKey(),
        'integration_id' => $integration->getKey(),
        'organization_id' => $organization->getKey(),
        'user_id' => $user->getKey(),
        'access_token_hash' => hash('sha256', $accessToken),
        'refresh_token_hash' => hash('sha256', $refreshToken),
        'refresh_token_expires_at' => now()->addDay(),
        'scopes' => ['organization:read'],
        'token_suffix' => 'ss-token',
    ]);

    $this->postJson('/integrations/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => 'partner-client',
        'client_secret' => $clientSecret,
        'code' => $authorizationCode,
        'redirect_uri' => 'https://partner.test/callback',
    ])->assertBadRequest()
        ->assertJsonPath('error', 'invalid_grant');

    $this->postJson('/integrations/oauth/token', [
        'grant_type' => 'refresh_token',
        'client_id' => 'partner-client',
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
    ])->assertBadRequest()
        ->assertJsonPath('error', 'invalid_grant');

    $this->withHeaders(['Authorization' => 'Bearer '.$accessToken])
        ->getJson('/api/integrations/v1/organization')
        ->assertForbidden();
});
