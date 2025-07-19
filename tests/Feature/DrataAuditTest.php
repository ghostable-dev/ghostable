<?php

use App\Environment\Actions\CreateEnvVariable;
use App\Environment\Entities\CreateEnvVariableData;
use App\Environment\Enums\EnvironmentType;
use App\Integrations\Drata\Jobs\SendAuditEvent;
use App\Core\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->team = $this->createTeam(name: 'Ray’s Occult Books', owner: $this->user);
    $project = $this->createProject(name: 'Website', team: $this->team);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
});

test('logging activity dispatches drata job', function () {
    Queue::fake();

    app(CreateEnvVariable::class)->handle(new CreateEnvVariableData(
        environment: $this->env,
        key: 'APP_NAME',
        value: 'test',
        createdBy: $this->user,
    ));

    Queue::assertPushed(SendAuditEvent::class);
});

test('drata job sends audit event', function () {
    $responseData = [];

    app(CreateEnvVariable::class)->handle(new CreateEnvVariableData(
        environment: $this->env,
        key: 'APP_URL',
        value: 'https://example.com',
        createdBy: $this->user,
    ));

    $activity = Activity::latest('id')->first();

    Http::fake();
    config(['drata.api_key' => 'test']);

    SendAuditEvent::dispatchSync($activity->id);

    Http::assertSent(function ($request) use (&$responseData) {
        $responseData = $request->data();
        return true;
    });

    expect($responseData['action'])->toBe('created');
    expect($responseData['description'])->not->toBe('');
});
