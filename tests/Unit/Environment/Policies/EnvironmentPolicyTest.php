<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Policies\EnvironmentPolicy;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Enums\OrganizationRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('view only allowed for organization members', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $policy = new EnvironmentPolicy;

    expect($policy->view($owner, $env))->toBeTrue();

    $outsider = $this->createUser('Other', 'other@example.com');
    expect($policy->view($outsider, $env))->toBeFalse();
});

test('manage settings requires permission', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $policy = new EnvironmentPolicy;
    expect($policy->manageSettings($owner, $env))->toBeTrue();

    $reader = $this->createUser('Reader', 'reader@example.com');
    $reader->organizationMembership()->assignToOrganization($org, OrganizationRole::DEVELOPER_READ_ONLY);
    expect($policy->manageSettings($reader, $env))->toBeFalse();
});

test('manage tokens limited to admins', function () {
    $admin = $this->createUser('Admin', 'admin@example.com');
    $org = $this->createOrganization('Org', $admin);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $policy = new EnvironmentPolicy;
    expect($policy->manageTokens($admin, $env))->toBeTrue();

    $dev = $this->createUser('Dev', 'dev@example.com');
    $dev->organizationMembership()->assignToOrganization($org, OrganizationRole::DEVELOPER);
    expect($policy->manageTokens($dev, $env))->toBeFalse();
});

test('perform checks environment permissions', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');
    $org = $this->createOrganization('Org', $owner);
    $project = $this->createProject('Proj', $org);
    $env = $this->createEnvironment('Env', EnvironmentType::DEVELOPMENT, $project);

    $policy = new EnvironmentPolicy;
    expect($policy->perform($owner, $env, OrganizationPermission::ViewSecrets))->toBeTrue();

    $billing = $this->createUser('Bill', 'bill@example.com');
    $billing->organizationMembership()->assignToOrganization($org, OrganizationRole::BILLING_ONLY);
    expect($policy->perform($billing, $env, OrganizationPermission::ViewSecrets))->toBeFalse();
});
