<?php

use App\Core\Models\Activity;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Integration\Integrations\Vanta\Jobs\SendAuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser(name: 'Ray', email: 'ray@ghostbusters.com');
    $this->organization = $this->createOrganization(name: 'Ray’s Occult Books', owner: $this->user);
    $project = $this->createProject(name: 'Website', organization: $this->organization);
    $this->env = $this->createEnvironment(name: 'Website', type: EnvironmentType::DEVELOPMENT, project: $project);
});

test('logging activity dispatches vanta job', function () {
    Queue::fake();

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->env,
        key: 'APP_NAME',
        value: 'test',
        createdBy: $this->user,
    ));

    Queue::assertPushed(SendAuditEvent::class);
});

test('vanta job sends audit event', function () {
    $responseData = [];

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->env,
        key: 'APP_URL',
        value: 'https://example.com',
        createdBy: $this->user,
    ));

    $activity = Activity::latest('id')->first();

    Http::fake();
    config(['vanta.access_token' => 'test-token']);

    SendAuditEvent::dispatchSync($activity->id);

    Http::assertSent(function ($request) use (&$responseData) {
        $responseData = $request->data();

        return true;
    });

    expect($responseData['action'])->toBe('created');
    expect($responseData['description'])->not->toBe('');
});
