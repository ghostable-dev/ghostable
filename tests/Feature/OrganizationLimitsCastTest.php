<?php

use App\Billing\Enums\Plan;
use App\Organization\Casts\OrganizationLimitsCast;
use App\Organization\Entities\OrganizationLimits;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Mockery::close();
});

it('returns free limits by default', function () {
    $organization = Organization::factory()->create();

    expect($organization->limits)
        ->toBeInstanceOf(OrganizationLimits::class)
        ->and($organization->limits->users)->toBe(2)
        ->and($organization->limits->api_operations)->toBe(5000);
});

it('stores limit overrides', function () {
    $organization = Organization::factory()->create();
    $organization->update(['limits' => OrganizationLimits::from(['users' => 5])]);

    expect($organization->fresh()->limits->users)->toBe(5);
});

it('returns standard limits for standard plans', function () {
    $organization = Mockery::mock(Organization::class);
    $organization->shouldReceive('getAttribute')->with('plan')->andReturn(Plan::STANDARD);

    $cast = new OrganizationLimitsCast;
    $limits = $cast->get($organization, 'limits', null, []);

    expect($limits)
        ->toBeInstanceOf(OrganizationLimits::class)
        ->and($limits->users)->toBe(5)
        ->and($limits->api_operations)->toBe(25000);
});
