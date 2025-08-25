<?php

use App\Environment\Enums\EnvironmentType;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

test('creating an environment secret logs activity', function () {
    $user = $this->createUser(name: 'Egon', email: 'egon@ghostbusters.com');
    $organization = $this->createOrganization(name: 'Ghostbusters', owner: $user);
    $project = $this->createProject(name: 'Proton Pack', organization: $organization);
    $environment = $this->createEnvironment(
        name: 'staging',
        type: EnvironmentType::STAGING,
        project: $project,
    );

    Sanctum::actingAs($user);

    $payload = [
        'name' => 'API_KEY',
        'value' => 'initial',
        'type' => SecretType::TOKEN->value,
        'metadata' => ['foo' => 'bar'],
    ];

    $this->postJson("/api/v1/projects/{$project->id}/environments/{$environment->name}/secrets", $payload)
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'API_KEY');

    $secret = Secret::where('environment_id', $environment->id)->where('name', 'API_KEY')->first();
    expect($secret)->not->toBeNull();

    $activity = Activity::query()
        ->where('log_name', 'secret')
        ->where('subject_id', $secret->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->event)->toBe('created');
    expect($activity->causer_id)->toBe($user->id);
    expect($activity->properties['environment_id'])->toBe($environment->id);
});
