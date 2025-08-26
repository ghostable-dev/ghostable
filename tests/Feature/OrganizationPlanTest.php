<?php

use App\Billing\Enums\Plan;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Mockery::close();
});

it('defaults to free plan', function () {
    $organization = Organization::factory()->create();

    expect($organization->plan)->toBe(Plan::FREE);
});

it('determines plan from subscriptions', function () {
    $organization = Mockery::mock(Organization::class)->makePartial();
    $organization->shouldReceive('subscribed')->with(Plan::ENTERPRISE)->andReturnFalse();
    $organization->shouldReceive('subscribed')->with(Plan::GROWTH)->andReturnTrue();
    $organization->shouldReceive('subscribed')->with(Plan::STARTER)->andReturnFalse();

    expect($organization->plan)->toBe(Plan::GROWTH);
});
