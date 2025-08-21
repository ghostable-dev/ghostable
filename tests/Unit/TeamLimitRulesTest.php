<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Rules\WithinProjectEnvironmentCap;
use App\Project\Models\Project;
use App\Team\Actions\CreateTeam;
use App\Team\Rules\WithinTeamProjectCap;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('passes when team project cap not reached', function () {
    $user = $this->createUser('A', 'a@example.com');
    $team = app(CreateTeam::class)->handle('Personal', $user, personal: true);
    $rule = new WithinTeamProjectCap($team);
    $validator = validator(['x' => null], ['x' => [$rule]]);
    expect($validator->passes())->toBeTrue();
});

it('fails when team project cap reached with message', function () {
    $user = $this->createUser('B', 'b@example.com');
    $team = app(CreateTeam::class)->handle('Personal', $user, personal: true);
    Project::factory()->forTeam($team)->create();
    $rule = new WithinTeamProjectCap($team);
    $validator = validator(['x' => null], ['x' => [$rule]]);
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('x'))->toBe('Project limit reached for this team.');
});

it('passes when project environment cap not reached', function () {
    $user = $this->createUser('C', 'c@example.com');
    $team = app(CreateTeam::class)->handle('Personal', $user, personal: true);
    $project = Project::factory()->forTeam($team)->create();
    $rule = new WithinProjectEnvironmentCap($project);
    $validator = validator(['x' => null], ['x' => [$rule]]);
    expect($validator->passes())->toBeTrue();
});

it('fails when project environment cap reached with message', function () {
    $user = $this->createUser('D', 'd@example.com');
    $team = app(CreateTeam::class)->handle('Personal', $user, personal: true);
    $project = Project::factory()->forTeam($team)->create();
    $max = config('ghostable.personal_limits.environments_per_project');
    for ($i = 0; $i < $max; $i++) {
        $this->createEnvironment('env'.$i, EnvironmentType::LOCAL, $project);
    }
    $rule = new WithinProjectEnvironmentCap($project);
    $validator = validator(['x' => null], ['x' => [$rule]]);
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('x'))->toBe('Environment limit reached for this project.');
});
