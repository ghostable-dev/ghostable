<?php

declare(strict_types=1);

use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentSecret;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('update variable returns deterministic version conflict payload', function (): void {
    $user = $this->createUser('Venkman', 'venkman@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);
    $project = $this->createProject('Containment Unit', $organization);
    $environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    EnvironmentSecret::query()->create([
        'environment_id' => $environment->id,
        'name' => 'APP_KEY',
        'ciphertext' => base64_encode('ciphertext'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $environment->id],
        'claims' => ['hmac' => 'hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fp-current',
        'metadata' => [],
        'line_bytes' => 16,
        'is_commented' => false,
        'version' => 3,
        'last_updated_by' => $user->id,
        'last_updated_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $endpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/variables/%s',
        $project->id,
        $environment->name,
        'APP_KEY',
    );

    $this->patchJson($endpoint, [
        'is_commented' => true,
        'if_version' => 2,
    ])
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'version_conflict')
        ->assertJsonPath('conflicts.0.key', 'APP_KEY')
        ->assertJsonPath('conflicts.0.server_version', 3)
        ->assertJsonPath('conflicts.0.client_if_version', 2);
});
