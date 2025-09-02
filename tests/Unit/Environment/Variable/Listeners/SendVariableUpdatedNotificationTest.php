<?php

use App\Account\Models\User;
use App\Core\Actions\GetNotifiableOrganizationUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Environment\Models\Environment;
use App\Environment\Variable\Events\VariableUpdated;
use App\Environment\Variable\Listeners\SendVariableUpdatedNotification;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('sends notification when enabled', function () {
    Notification::fake();

    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create(['name' => 'dev', 'type' => 'development']);
    $user = User::factory()->create();
    $project->organization->users()->attach($user);

    $variable = EnvironmentVariable::factory()->forEnvironment($env)->make();

    app()->instance(IsNotificationEnabled::class, new class
    {
        public function handle(...$args)
        {
            return true;
        }
    });
    app()->instance(GetNotifiableOrganizationUsers::class, new class($user)
    {
        public function __construct(private $user) {}

        public function handle($organization)
        {
            return collect([$this->user]);
        }
    });

    $listener = new SendVariableUpdatedNotification;
    $listener->handle(new VariableUpdated($variable));

    Notification::assertSentTo($user, \App\Environment\Variable\Notifications\VariableUpdatedNotification::class);
});

it('does nothing when notifications disabled', function () {
    Notification::fake();

    $project = Project::factory()->create();
    $env = Environment::factory()->forProject($project)->create(['name' => 'dev', 'type' => 'development']);
    $variable = EnvironmentVariable::factory()->forEnvironment($env)->make();

    app()->instance(IsNotificationEnabled::class, new class
    {
        public function handle(...$args)
        {
            return false;
        }
    });
    app()->instance(GetNotifiableOrganizationUsers::class, new class
    {
        public function handle($organization)
        {
            return collect();
        }
    });

    $listener = new SendVariableUpdatedNotification;
    $listener->handle(new VariableUpdated($variable));

    Notification::assertNothingSent();
});
