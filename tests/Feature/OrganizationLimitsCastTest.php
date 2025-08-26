<?php

use App\Organization\Casts\OrganizationLimitsCast;
use App\Organization\Entities\FreeOrganizationLimits;
use App\Organization\Entities\StarterOrganizationLimits;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Mockery::close();
});

it('returns free limits by default', function () {
    $organization = Organization::factory()->create();

    expect($organization->limits)
        ->toBeInstanceOf(FreeOrganizationLimits::class)
        ->and($organization->limits->users)->toBe(2);
});

it('stores limit overrides', function () {
    $organization = Organization::factory()->create();
    $organization->update(['limits' => FreeOrganizationLimits::from(['users' => 5])]);

    expect($organization->fresh()->limits->users)->toBe(5);
});

it('returns starter limits for starter plans', function () {
    $organization = Mockery::mock(Organization::class)->makePartial();
    $organization->shouldReceive('isStarter')->andReturnTrue();
    $organization->shouldReceive('isGrowth')->andReturnFalse();

    $cast = new OrganizationLimitsCast();
    $limits = $cast->get($organization, 'limits', null, []);

    expect($limits)
        ->toBeInstanceOf(StarterOrganizationLimits::class)
        ->and($limits->users)->toBe(5);
});
