<?php

declare(strict_types=1);

use App\Environment\Enums\EnvironmentType;
use App\Environment\Livewire\EnvironmentSecretDetailsViewer;
use App\Environment\Livewire\EnvironmentSecretManager;
use App\Environment\Livewire\EnvironmentSecretVersionManager;
use App\Environment\Models\EnvironmentSecret;
use App\Environment\Models\EnvironmentSecretVersion;
use App\Environment\Models\EnvironmentVariableComment;
use App\Environment\Models\EnvironmentVariableNote;
use App\Environment\Models\EnvironmentVariableVersionChangeNote;
use App\Organization\Actions\CreatePermissionOverride;
use App\Organization\Enums\OrganizationPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('variable detail and history UI surfaces encrypted context metadata', function (): void {
    $user = $this->createUser('Owner', 'owner@example.com');
    $organization = $this->createOrganization('Ghostable', $user);
    $project = $this->createProject('Containment Unit', $organization);
    $environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    $secret = EnvironmentSecret::query()->create([
        'environment_id' => $environment->id,
        'name' => 'APP_KEY',
        'ciphertext' => base64_encode('ciphertext-current'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $environment->id],
        'claims' => ['hmac' => 'current-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fp-current',
        'metadata' => [],
        'line_bytes' => 24,
        'is_commented' => false,
        'version' => 2,
        'last_updated_by' => $user->id,
        'last_updated_at' => now(),
    ]);

    EnvironmentVariableNote::query()->create([
        'environment_secret_id' => $secret->id,
        'ciphertext' => base64_encode('Encrypted note'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['scope' => 'note'],
        'claims' => ['meta' => ['body_length' => 14]],
        'client_sig' => base64_encode(random_bytes(64)),
        'created_by' => $user->id,
        'last_updated_by' => $user->id,
    ]);

    EnvironmentVariableComment::query()->create([
        'environment_secret_id' => $secret->id,
        'ciphertext' => base64_encode('Encrypted comment'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['scope' => 'comment'],
        'claims' => ['meta' => ['body_length' => 17]],
        'client_sig' => base64_encode(random_bytes(64)),
        'created_by' => $user->id,
    ]);

    $firstVersion = EnvironmentSecretVersion::query()->create([
        'environment_secret_id' => $secret->id,
        'version' => 1,
        'name' => 'APP_KEY',
        'ciphertext' => base64_encode('first'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $environment->id],
        'claims' => ['hmac' => 'first-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fp-first',
        'metadata' => [],
        'line_bytes' => 20,
        'is_commented' => false,
        'changed_by' => $user->id,
        'created_at' => now()->subHour(),
    ]);

    $secondVersion = EnvironmentSecretVersion::query()->create([
        'environment_secret_id' => $secret->id,
        'version' => 2,
        'name' => 'APP_KEY',
        'ciphertext' => base64_encode('second'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $environment->id],
        'claims' => ['hmac' => 'second-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fp-second',
        'metadata' => [],
        'line_bytes' => 22,
        'is_commented' => false,
        'changed_by' => $user->id,
        'created_at' => now(),
    ]);

    EnvironmentVariableVersionChangeNote::query()->create([
        'environment_secret_version_id' => $secondVersion->id,
        'ciphertext' => base64_encode('Encrypted reason'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['scope' => 'change_note'],
        'claims' => ['meta' => ['body_length' => 16]],
        'client_sig' => base64_encode(random_bytes(64)),
        'created_by' => $user->id,
    ]);

    $this->actingAs($user);

    $detailsViewer = Livewire::test(EnvironmentSecretDetailsViewer::class)
        ->set('environmentSecretId', $secret->id)
        ->set('showing', true)
        ->assertSet('tab', 'info')
        ->assertSee('Info')
        ->assertSee('Last Updated By')
        ->assertSee('Comments')
        ->assertDontSee('Comments enabled');

    $detailsViewer
        ->set('tab', 'note')
        ->assertSee('Description / Note')
        ->assertSee('Trusted client editing')
        ->assertSee('Encrypted note stored')
        ->assertSee('To view or edit, open in a trusted client.')
        ->assertSee('Open in desktop')
        ->assertDontSee('Editable from trusted clients');

    $detailsViewer
        ->set('tab', 'comments')
        ->assertSee('Encrypted comment')
        ->assertSee('To view, open in a trusted client.')
        ->assertSeeHtml('ghostable-local://environment/'.$organization->id.'/'.$project->id.'/'.$environment->id);

    Livewire::test(EnvironmentSecretManager::class, ['environment' => $environment])
        ->assertSee('Open in desktop')
        ->assertSeeHtml('ghostable-local://environment/'.$organization->id.'/'.$project->id.'/'.$environment->id);

    Livewire::test(EnvironmentSecretVersionManager::class)
        ->set('environmentSecretId', $secret->id)
        ->set('showing', true)
        ->assertSee('Reason for change')
        ->assertSee('Encrypted change reason saved');
});

test('variable detail and history UI show locked context states without permission', function (): void {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $reader = $this->createUser('Reader', 'reader@example.com');
    $organization = $this->createOrganization('Ghostable', $owner, [$reader]);
    $project = $this->createProject('Containment Unit', $organization);
    $environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);
    $project->update(['is_restricted' => true]);
    $environment->update(['is_restricted' => true]);

    $secret = EnvironmentSecret::query()->create([
        'environment_id' => $environment->id,
        'name' => 'APP_KEY',
        'ciphertext' => base64_encode('ciphertext-current'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $environment->id],
        'claims' => ['hmac' => 'current-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fp-current',
        'metadata' => [],
        'line_bytes' => 24,
        'is_commented' => false,
        'version' => 1,
        'last_updated_by' => $owner->id,
        'last_updated_at' => now(),
    ]);

    EnvironmentSecretVersion::query()->create([
        'environment_secret_id' => $secret->id,
        'version' => 1,
        'name' => 'APP_KEY',
        'ciphertext' => base64_encode('first'),
        'nonce' => base64_encode(random_bytes(24)),
        'alg' => 'xchacha20-poly1305',
        'aad' => ['env' => (string) $environment->id],
        'claims' => ['hmac' => 'first-hmac'],
        'client_sig' => base64_encode(random_bytes(64)),
        'env_kek_version' => 1,
        'env_kek_fingerprint' => 'fp-first',
        'metadata' => [],
        'line_bytes' => 20,
        'is_commented' => false,
        'changed_by' => $owner->id,
        'created_at' => now(),
    ]);

    app(CreatePermissionOverride::class)->handle(
        $reader,
        $environment,
        OrganizationPermission::ViewVariables,
        $owner
    );

    $this->actingAs($reader);

    $detailsViewer = Livewire::test(EnvironmentSecretDetailsViewer::class)
        ->set('environmentSecretId', $secret->id)
        ->set('showing', true)
        ->assertSet('tab', 'info')
        ->assertSee('Info')
        ->assertSee('Comments')
        ->assertDontSee('Comments enabled');

    $detailsViewer
        ->set('tab', 'note')
        ->assertSee('Description / Note')
        ->assertSee('You do not have permission to view variable context.')
        ->assertDontSee('Open in desktop');

    $detailsViewer
        ->set('tab', 'comments')
        ->assertSee('Comment history is unavailable without context access.');

    Livewire::test(EnvironmentSecretVersionManager::class)
        ->set('environmentSecretId', $secret->id)
        ->set('showing', true)
        ->assertSee('Reason for change')
        ->assertSee('Locked');
});
