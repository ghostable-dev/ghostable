<?php

use App\Account\Models\User;
use App\Environment\Actions\Token\DeleteEnvToken;
use App\Environment\Actions\Token\LogEnvTokenActivity;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\Environment;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('deletes env token and logs activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $environment = Environment::factory()->forProject($project)->create([
        'type' => EnvironmentType::PRODUCTION->value,
    ]);

    $token = $environment->createToken('cli');
    $tokenModel = $token->accessToken;

    $mock = Mockery::mock(LogEnvTokenActivity::class);
    $mock->shouldReceive('handle')
        ->once()
        ->with($tokenModel, 'deleted', $user);
    app()->instance(LogEnvTokenActivity::class, $mock);

    app(DeleteEnvToken::class)->handle($tokenModel, $user);

    expect($environment->tokens()->count())->toBe(0);
});
