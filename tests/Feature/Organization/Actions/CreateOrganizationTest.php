<?php

use App\Billing\Enums\BillingPolicy;
use App\Billing\Enums\Plan;
use App\Organization\Actions\CreateOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('plan overrides force manual billing policy on creation', function () {
    $owner = $this->createUser('Owner', 'owner@example.com');

    $organization = app(CreateOrganization::class)->handle(
        name: 'Acme',
        owner: $owner,
        planOverride: Plan::SCALE,
    )->fresh();

    expect($organization->billing_policy)->toBe(BillingPolicy::MANUAL_OVERRIDE)
        ->and($organization->plan_override)->toBe(Plan::SCALE);
});
