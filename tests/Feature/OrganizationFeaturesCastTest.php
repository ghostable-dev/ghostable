<?php

use App\Billing\Enums\Plan;
use App\Organization\Casts\OrganizationFeaturesCast;
use App\Organization\Entities\OrganizationFeatures;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Mockery::close();
});

it('returns free features by default', function () {
    $organization = Organization::factory()->create();

    expect($organization->features)
        ->toBeInstanceOf(OrganizationFeatures::class)
        ->and($organization->features->audits)->toBeFalse()
        ->and($organization->features->integrations)->toBeFalse()
        ->and($organization->features->advanced_permissions)->toBeFalse();
});

it('stores feature overrides', function () {
    $organization = Organization::factory()->create();
    $organization->update(['features' => OrganizationFeatures::from(['audits' => true])]);

    expect($organization->fresh()->features->audits)->toBeTrue();
});

it('returns standard features for standard plans', function () {
    $organization = Mockery::mock(Organization::class);
    $organization->shouldReceive('getAttribute')->with('plan')->andReturn(Plan::STANDARD);

    $cast = new OrganizationFeaturesCast;
    $features = $cast->get($organization, 'features', null, []);

    expect($features)
        ->toBeInstanceOf(OrganizationFeatures::class)
        ->and($features->audits)->toBeTrue()
        ->and($features->integrations)->toBeTrue()
        ->and($features->advanced_permissions)->toBeFalse();
});
