<?php

use App\Environment\Actions\Token\CreateEnvToken;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use App\Project\Enums\DeploymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = $this->createUser('Egon', 'egon@ghostbusters.com');
    $this->organization = $this->createOrganization('Ghostbusters', $this->user);
    $this->project = $this->createProject('Containment', $this->organization);
    $this->environment = $this->createEnvironment('production', EnvironmentType::PRODUCTION, $this->project);
    $this->endpoint = '/api/v1/ci/deploy/provider';
    $this->authHeaders = fn () => ['Authorization' => 'Bearer '.$this->token->plainTextToken];

    $this->variables = [
        'APP_NAME' => 'Ghostable',
        'APP_ENV' => 'production',
        'APP_URL' => 'https://ghostable.dev',
        'APP_DEBUG' => 'false',
        'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    ];

    foreach ($this->variables as $key => $value) {
        app(CreateVariable::class)->handle(new CreateVariableData(
            environment: $this->environment,
            key: $key,
            value: $value,
        ));
    }

    $this->token = app(CreateEnvToken::class)
        ->handle(name: 'deploy', environment: $this->environment);
});

function postPlan(array $body = [])
{
    return test()->withHeaders((test()->authHeaders)())
        ->postJson(test()->endpoint, $body);
}

// function postPlan(array $query = [])
// {
//     $url = test()->endpoint;

//     if (! empty($query)) {
//         $url .= '?'.http_build_query($query);
//     }

//     return test()->withHeaders([
//         'Authorization' => 'Bearer '.test()->token->plainTextToken,
//     ])->getJson($url);
// }

it('returns plain variables when encryption flag is absent', function () {
    $this->project->update(['deployment_provider' => DeploymentProvider::LARAVEL_FORGE]);

    $response = postPlan();

    $response->assertOk();

    $data = $response->json('data');

    expect($data['encrypted'])->toBeNull();

    $standardKeys = collect($data['standard'])->pluck('key');

    expect($standardKeys)->toContain('APP_NAME', 'APP_ENV', 'APP_URL')
        ->and($standardKeys)->not->toContain('LARAVEL_ENV_ENCRYPTION_KEY');
});

it('encrypts forge payload when requested via query flag', function () {
    $this->project->update(['deployment_provider' => DeploymentProvider::LARAVEL_FORGE]);

    $response = postPlan(['encrypted' => 1]);

    $response->assertOk();

    $data = $response->json('data');

    expect($data['encrypted'])->not->toBeNull();

    $standardKeys = collect($data['standard'])->pluck('key');

    expect($standardKeys)->toContain('LARAVEL_ENV_ENCRYPTION_KEY')
        ->and($standardKeys)->not->toContain('APP_NAME');
});

it('includes vapor secrets flagged on variables', function () {
    $this->project->update(['deployment_provider' => DeploymentProvider::LARAVEL_VAPOR]);

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'STRIPE_SECRET',
        value: 'sk_test_123',
        is_vapor_secret: true,
    ));

    $response = postPlan();

    $response->assertOk();

    $data = $response->json('data');

    $secretKeys = collect($data['secret'])->pluck('key');
    $standardKeys = collect($data['standard'])->pluck('key');

    expect($secretKeys)->toContain('STRIPE_SECRET')
        ->and($standardKeys)->not->toContain('STRIPE_SECRET');
});

it('excludes vapor secrets from encrypted bundle', function () {
    $this->project->update(['deployment_provider' => DeploymentProvider::LARAVEL_VAPOR]);

    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $this->environment,
        key: 'STRIPE_SECRET',
        value: 'sk_test_123',
        is_vapor_secret: true,
    ));

    $response = postPlan(['encrypted' => 1]);

    $response->assertOk();

    $data = $response->json('data');

    expect($data['encrypted'])->not->toBeNull();

    $standardKeys = collect($data['standard'])->pluck('key');
    $secretKeys = collect($data['secret'])->pluck('key');

    expect($standardKeys)->toContain('LARAVEL_ENV_ENCRYPTION_KEY')
        ->and($standardKeys)->not->toContain('STRIPE_SECRET')
        ->and($secretKeys)->toContain('STRIPE_SECRET');
});
