<?php

declare(strict_types=1);

use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = $this->createUser('Nat', 'nat@example.com');
    $this->organization = $this->createOrganization('Spook Central', $this->user);
    $this->project = $this->createProject('Containment Unit', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);

    $this->variableName = 'DB_PASSWORD';
    $this->endpoint = sprintf(
        '/api/v2/projects/%s/environments/%s/variables/%s/history',
        $this->project->id,
        $this->environment->name,
        $this->variableName
    );

    $this->secret = EnvironmentSecret::query()->create([
        'environment_id' => $this->environment->id,
        'name' => $this->variableName,
        'ciphertext' => base64_encode('current-secret'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $this->environment->id],
        'claims' => ['hmac' => 'current-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 2,
        'env_kek_fingerprint' => 'fingerprint-current',
        'metadata' => ['laravel' => ['is_vapor_secret' => false]],
        'line_bytes' => 64,
        'is_commented' => false,
        'version' => 2,
        'last_updated_by' => $this->user->id,
        'last_updated_at' => now(),
    ]);

    $this->firstVersion = EnvironmentSecretVersion::query()->create([
        'environment_secret_id' => $this->secret->id,
        'version' => 1,
        'name' => $this->variableName,
        'ciphertext' => base64_encode('first-version'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $this->environment->id],
        'claims' => ['hmac' => 'first-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fingerprint-first',
        'metadata' => ['laravel' => ['is_vapor_secret' => false]],
        'line_bytes' => 32,
        'is_commented' => false,
        'changed_by' => $this->user->id,
        'created_at' => now()->subDays(2),
    ]);

    $this->secondVersion = EnvironmentSecretVersion::query()->create([
        'environment_secret_id' => $this->secret->id,
        'version' => 2,
        'name' => $this->variableName,
        'ciphertext' => base64_encode('second-version'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $this->environment->id],
        'claims' => ['hmac' => 'second-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 2,
        'env_kek_fingerprint' => 'fingerprint-second',
        'metadata' => ['laravel' => ['is_vapor_secret' => true]],
        'line_bytes' => 48,
        'is_commented' => true,
        'changed_by' => $this->user->id,
        'created_at' => now()->subDay(),
    ]);
});

test('variable history entries expose version identifiers needed for rollback', function (): void {
    Sanctum::actingAs($this->user);

    $this->getJson($this->endpoint)
        ->assertOk()
        ->assertJsonPath('data.entries.0.version_id', (string) $this->secondVersion->id)
        ->assertJsonPath('data.entries.1.version_id', (string) $this->firstVersion->id)
        ->assertJsonPath('data.variable.version_id', (string) $this->secondVersion->id);
});

test('variable history activity uses desktop source when requested by the desktop client', function (): void {
    Sanctum::actingAs($this->user);

    $this->withHeaders([
        'X-Ghostable-Client-Type' => 'desktop',
    ])->getJson($this->endpoint)->assertOk();

    $activity = Activity::query()
        ->where('event', 'variable_history_viewed')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'source'))->toBe('desktop');
});
