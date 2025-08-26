<?php

use App\Organization\Casts\OrganizationFeaturesCast;
use App\Organization\Entities\FreeOrganizationFeatures;
use App\Organization\Entities\StarterOrganizationFeatures;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Mockery::close();
});

it('returns free features by default', function () {
    $organization = Organization::factory()->create();

    expect($organization->features)
        ->toBeInstanceOf(FreeOrganizationFeatures::class)
        ->and($organization->features->audits)->toBeFalse();
});

it('stores feature overrides', function () {
    $organization = Organization::factory()->create();
    $organization->update(['features' => ['audits' => true]]);

    expect($organization->fresh()->features->audits)->toBeTrue();
});

it('returns starter features for starter plans', function () {
    $organization = Mockery::mock(Organization::class)->makePartial();
    $organization->shouldReceive('isStarter')->andReturnTrue();
    $organization->shouldReceive('isGrowth')->andReturnFalse();

    $cast = new OrganizationFeaturesCast();
    $features = $cast->get($organization, 'features', null, []);

    expect($features)
        ->toBeInstanceOf(StarterOrganizationFeatures::class)
        ->and($features->audits)->toBeTrue();
});
