<?php

use App\Environment\Enums\EnvironmentType;
use App\Secret\Actions\CreateSecret;
use App\Secret\Enums\SecretType;
use App\Secret\Livewire\SecretNotificationsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('secret notifications manager toggles notifications', function () {
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

    $secret->notifications = new \App\Secret\Entities\SecretNotificationsData();
    $secret->save();

    Livewire::actingAs($user)
        ->test(SecretNotificationsManager::class, ['secret' => $secret->id])
        ->call('toggle', 'secret_updated');

    expect($secret->fresh()->notifications->secret_updated)->toBeFalse();
});
