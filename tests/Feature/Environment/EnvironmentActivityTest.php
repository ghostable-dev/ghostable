<?php

use App\Billing\Enums\Plan;
use App\Core\Actions\StreamActivityCsv;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Livewire\EnvironmentActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('environment activity csv can be downloaded', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user, planOverride: Plan::STANDARD);
    $project = $this->createProject('Website', $org);
    $environment = $this->createEnvironment('Production', EnvironmentType::PRODUCTION, $project);

    $this->actingAs($user);

    $streamResponse = response()->streamDownload(fn () => null, 'environment-production-activity.csv');

    $exporter = \Mockery::mock(StreamActivityCsv::class);
    $exporter->shouldReceive('handle')
        ->once()
        ->withArgs(function ($query, $filename, $context) use ($environment, $project) {
            expect($filename)->toBe('environment-production-activity.csv');
            expect($context)->toMatchArray([
                'project_name' => $project->name,
                'project_id' => $project->id,
                'environment_id' => $environment->id,
            ]);

            return $query instanceof Builder;
        })
        ->andReturn($streamResponse);

    $this->app->instance(StreamActivityCsv::class, $exporter);

    Livewire::test(EnvironmentActivity::class, ['environment' => $environment])
        ->call('download')
        ->assertFileDownloaded('environment-production-activity.csv');
});
