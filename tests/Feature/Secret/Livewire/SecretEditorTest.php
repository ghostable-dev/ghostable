<?php

use App\Environment\Enums\EnvironmentType;
use App\Secret\Actions\CreateSecret;
use App\Secret\Enums\SecretType;
use App\Secret\Livewire\SecretEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('secret editor updates a secret', function () {
    config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);

    $user = $this->createUser('Egon', 'egon@example.com');
    $organization = $this->createOrganization('Ghostbusters', $user);
    $project = $this->createProject('Website', $organization);
    $environment = $this->createEnvironment('Development', EnvironmentType::DEVELOPMENT, $project);

    $secret = app(CreateSecret::class)->handle(
        environment: $environment,
        name: 'API_KEY',
        type: SecretType::TOKEN,
        value: 'value',
        metadata: null,
        createdBy: $user,
    );

    Livewire::actingAs($user)
        ->test(SecretEditor::class)
        ->call('launchEditorModal', $secret)
        ->set('name', 'NEW_API_KEY')
        ->set('value', 'new-value')
        ->call('updateSecret');

    expect($secret->fresh()->name)->toBe('NEW_API_KEY');
});
