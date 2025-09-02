<?php

use App\Environment\Enums\EnvironmentType;
use App\Secret\Enums\SecretType;
use App\Secret\Livewire\SecretsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('secrets manager creates a secret', function () {
    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

    $user = $this->createUser('Egon', 'egon@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);
    $project = $this->createProject('Website', $organization);
    $environment = $this->createEnvironment('Development', EnvironmentType::DEVELOPMENT, $project);

    Livewire::actingAs($user)
        ->test(SecretsManager::class, ['environment' => $environment->id])
        ->set('name', 'DB_PASSWORD')
        ->set('value', 'secret-value')
        ->set('type', SecretType::TOKEN->value)
        ->call('createSecret');

    expect($environment->secrets()->where('name', 'DB_PASSWORD')->exists())->toBeTrue();
});
