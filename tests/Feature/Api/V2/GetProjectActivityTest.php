<?php

use App\Billing\Enums\Plan;
use App\Environment\Enums\EnvironmentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('project activity includes environment key reshare events', function (): void {
    $user = $this->createUser('Winston', 'winston@ghostable.com');
    $organization = $this->createOrganization('Ghostbusters', $user, [], Plan::STANDARD);
    $project = $this->createProject('Containment Unit', $organization);
    $environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $project);

    activity('variable')
        ->performedOn($environment)
        ->causedBy($user)
        ->event('environment_key_reshared')
        ->log('Re-shared environment key.');

    Sanctum::actingAs($user);

    $this->getJson("/api/v2/projects/{$project->id}/activity")
        ->assertOk()
        ->assertJsonPath('data.0.event', 'environment_key_reshared')
        ->assertJsonPath('data.0.subject.type', 'environment');
});
