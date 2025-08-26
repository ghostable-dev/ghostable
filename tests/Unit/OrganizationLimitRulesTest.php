<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Rules\WithinProjectEnvironmentCap;
use App\Organization\Actions\CreateOrganization;
use App\Organization\Rules\WithinOrganizationProjectCap;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('passes when organization project cap not reached', function () {
    $user = $this->createUser('A', 'a@example.com');
    $organization = app(CreateOrganization::class)->handle('Personal', $user, personal: true);
    $rule = new WithinOrganizationProjectCap($organization);
    $validator = validator(['x' => null], ['x' => [$rule]]);
    expect($validator->passes())->toBeTrue();
});

it('fails when organization project cap reached with message', function () {
    $user = $this->createUser('B', 'b@example.com');
    $organization = app(CreateOrganization::class)->handle('Personal', $user, personal: true);
    Project::factory()->forOrganization($organization)->create();
    $rule = new WithinOrganizationProjectCap($organization);
    $validator = validator(['x' => null], ['x' => [$rule]]);
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('x'))->toBe('Project limit reached for this organization.');
});

it('passes when project environment cap not reached', function () {
    $user = $this->createUser('C', 'c@example.com');
    $organization = app(CreateOrganization::class)->handle('Personal', $user, personal: true);
    $project = Project::factory()->forOrganization($organization)->create();
    $rule = new WithinProjectEnvironmentCap($project);
    $validator = validator(['x' => null], ['x' => [$rule]]);
    expect($validator->passes())->toBeTrue();
});

it('fails when project environment cap reached with message', function () {
    $user = $this->createUser('D', 'd@example.com');
    $organization = app(CreateOrganization::class)->handle('Personal', $user, personal: true);
    $project = Project::factory()->forOrganization($organization)->create();
    $max = config('ghostable.personal_limits.environments_per_project');
    for ($i = 0; $i < $max; $i++) {
        $this->createEnvironment('env'.$i, EnvironmentType::LOCAL, $project);
    }
    $rule = new WithinProjectEnvironmentCap($project);
    $validator = validator(['x' => null], ['x' => [$rule]]);
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('x'))->toBe('Environment limit reached for this project.');
});
