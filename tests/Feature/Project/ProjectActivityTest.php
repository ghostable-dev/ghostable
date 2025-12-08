<?php

use App\Billing\Enums\Plan;
use App\Core\Actions\StreamActivityCsv;
use App\Project\Livewire\ProjectActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('project activity can be viewed', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user);
    $project = $this->createProject('Website', $org);

    $this->actingAs($user);

    $component = Livewire::test(ProjectActivity::class, ['project' => $project])
        ->call('refreshActivities');

    expect($component->get('activities')->total())->toBeGreaterThanOrEqual(1);

    $component->assertViewIs('project.project-activity');
});

test('project activity csv can be downloaded', function () {
    $user = $this->createUser('User', 'user@example.com');
    $org = $this->createOrganization('Org', $user, planOverride: Plan::STANDARD);
    $project = $this->createProject('Website', $org);

    $this->actingAs($user);

    $streamResponse = response()->streamDownload(fn () => null, 'project-website-activity.csv');

    $exporter = \Mockery::mock(StreamActivityCsv::class);
    $exporter->shouldReceive('handle')
        ->once()
        ->withArgs(function ($query, $filename, $context) use ($project) {
            expect($filename)->toBe('project-website-activity.csv');
            expect($context)->toMatchArray([
                'project_name' => $project->name,
                'project_id' => $project->id,
            ]);

            return $query instanceof Builder;
        })
        ->andReturn($streamResponse);

    $this->app->instance(StreamActivityCsv::class, $exporter);

    Livewire::test(ProjectActivity::class, ['project' => $project])
        ->call('download')
        ->assertFileDownloaded('project-website-activity.csv');
});
